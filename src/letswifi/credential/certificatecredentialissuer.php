<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\credential;

use DateTimeImmutable;
use DateTimeInterface;
use DomainException;
use PDO;
use fyrkat\openssl\CSR;
use fyrkat\openssl\DN;
use fyrkat\openssl\OpenSSLConfig;
use fyrkat\openssl\OpenSSLKey;
use fyrkat\openssl\PKCS12;
use fyrkat\openssl\PrivateKey;
use fyrkat\openssl\X509;
use letswifi\auth\User;
use letswifi\profile\ProfileService;
use letswifi\profile\Provider;
use letswifi\profile\Realm;

/**
 * @implements CredentialIssuer<PKCS12>
 *
 * @internal
 */
class CertificateCredentialIssuer implements CredentialIssuer
{
	public const DATE_FORMAT = 'Y-m-d H:i:s';

	/** @param Closure(string):void $revoke */
	public function __construct(
		public readonly User $user,
		public readonly Realm $realm,
		public readonly Provider $provider,
		public readonly DateTimeImmutable $now,
		private readonly ProfileService $profileService,
		private readonly \Closure $revoke,
	) {
	}

	public function issue(): CertificateCredential
	{
		$pkcs12 = $this->generateClientCertificate();
		$credentialId = (string)$pkcs12->x509->getSubject();

		return new CertificateCredential(
			credentialId: $credentialId,
			userId: $this->user->userId,
			clientId: $this->user->clientId,
			grantSid: $this->user->grantSid,
			ip: $this->user->ip,
			userAgent: $this->user->userAgent,
			realm: $this->realm,
			pkcs12: $pkcs12,
		);
	}

	/**
	 * @suppress PhanPossiblyNonClassMethodCall Phan doesn't understand PDO
	 * @suppress PhanPossiblyFalseTypeArgumentInternal Assume getTimestamp() doesn't return false
	 */
	private function logPreparedUserCredential( X509 $caCert, CSR $csr, DateTimeInterface $expiry, string $ident, string $usage ): int
	{
		$csrData = $csr->getCSRPem();
		$pdo = $this->profileService->getPDO();
		$statement = $pdo->prepare( <<<SQL
			INSERT INTO realm_signing_log
				(realm, requester, ident, "grant", ca_sub, sub, "usage", issued, expires, csr, client, user_agent, ip)
			VALUES
				(:realm, :requester, :ident, :grant, :ca_sub, :sub, :usage, :issued, :expires, :csr, :client, :user_agent, :ip)
			SQL );
		$statement->bindValue( 'realm', $this->realm->realmId, PDO::PARAM_STR );
		$statement->bindValue( 'requester', $this->user->userId, PDO::PARAM_STR );
		$statement->bindValue( 'ident', $ident, PDO::PARAM_STR );
		$statement->bindValue( 'grant', $this->user->grantSid, PDO::PARAM_STR );
		$statement->bindValue( 'ca_sub', $caCert->getSubject(), PDO::PARAM_STR );
		$statement->bindValue( 'sub', $csr->getSubject(), PDO::PARAM_STR );
		$statement->bindValue( 'usage', $usage, PDO::PARAM_STR );
		$statement->bindValue( 'issued', \gmdate( static::DATE_FORMAT, $this->now->getTimestamp() ), PDO::PARAM_STR );
		$statement->bindValue( 'expires', \gmdate( static::DATE_FORMAT, $expiry->getTimestamp() ), PDO::PARAM_STR );
		$statement->bindValue( 'csr', $csrData, PDO::PARAM_STR );
		$statement->bindValue( 'client', $this->user->clientId, PDO::PARAM_STR );
		$statement->bindValue( 'user_agent', $this->user->userAgent, PDO::PARAM_STR );
		$statement->bindValue( 'ip', $this->user->ip, PDO::PARAM_STR );
		$statement->execute();
		$last = $pdo->lastInsertId();
		$lastId = (int)$last;
		if ( 0 < $lastId && (string)$lastId === $last ) {
			return $lastId;
		}

		throw new DomainException( 'Unable to retrieve last insert ID from database' );
	}

	/**
	 * @suppress PhanPossiblyNonClassMethodCall Phan doesn't understand PDO
	 * @suppress PhanPossiblyFalseTypeArgumentInternal Assume getTimestamp() doesn't return false
	 */
	private function logCompletedUserCredential( X509 $userCert, string $usage ): void
	{
		$pdo = $this->profileService->getPDO();
		$statement = $pdo->prepare( <<<SQL
			UPDATE realm_signing_log
				SET
					issued = :issued,
					expires = :expires,
					x509 = :x509
				WHERE
					"serial" = :serial
					AND realm = :realm
					AND requester = :requester
					AND usage = :usage
					AND ca_sub = :ca_sub
			SQL );
		$statement->bindValue( 'issued', \gmdate( static::DATE_FORMAT, $userCert->getValidFrom()->getTimestamp() ), PDO::PARAM_STR );
		$statement->bindValue( 'expires', \gmdate( static::DATE_FORMAT, $userCert->getValidTo()->getTimestamp() ), PDO::PARAM_STR );
		$statement->bindValue( 'x509', $userCert->getX509Pem(), PDO::PARAM_STR );
		$statement->bindValue( 'serial', $userCert->getSerialNumber(), PDO::PARAM_INT );
		$statement->bindValue( 'realm', $this->realm->realmId, PDO::PARAM_STR );
		$statement->bindValue( 'requester', $this->user->userId, PDO::PARAM_STR );
		$statement->bindValue( 'usage', $usage, PDO::PARAM_STR );
		$statement->bindValue( 'ca_sub', $userCert->getIssuerSubject(), PDO::PARAM_STR );
		$statement->execute();
		$rows = $statement->rowCount();
		if ( 1 !== $rows ) {
			throw new DomainException( "Unable to log signed certificate; expected 1 rows to be updated but got {$rows}" );
		}
	}

	private function generateClientCertificate(): PKCS12
	{
		// TODO check that $expiry is not too far in the future,
		// during some test we ended up with the date 88363-05-14 and MySQL didn't like
		$expiry = $this->now->add( $this->realm->validity );
		$userKey = new PrivateKey( new OpenSSLConfig( privateKeyType: OpenSSLKey::KEYTYPE_RSA ) );
		$ident = $this->generateIdent();
		$dn = new DN( ['CN' => $ident] );
		$csr = CSR::generate( $dn, $userKey );

		$caCert = $this->realm->getSignerCertificate();

		$serial = $this->logPreparedUserCredential( $caCert, $csr, $expiry, $ident, 'client' );

		$caKey = $this->profileService->getPrivateKey( $this->realm->signer );

		$conf = new OpenSSLConfig( x509Extensions: OpenSSLConfig::X509_EXTENSION_CLIENT );
		$userCert = $csr->sign( $caCert, $caKey, $expiry, $conf, $serial );
		$this->logCompletedUserCredential( $userCert, 'client' );

		return new PKCS12( $userCert, $userKey, [$caCert] );
	}

	/**
	 * Create random string that ends with @realm
	 */
	private function generateIdent(): string
	{
		$realm = \rawurlencode( $this->realm->realmId );
		if ( \strlen( $realm ) > 47 ) {
			throw new DomainException( 'Realm is too long to fit in certificate' );
		}

		// $local will be 16 bytes long
		$local = \strtolower( \strtr( \base64_encode( \random_bytes( 12 ) ), '/+9876', '012345' ) );
		\assert( \strlen( $local ) === 16 );

		// result is at most 47 + 1 + 16 = 64 bytes
		return "{$local}@{$realm}";
	}
}
