<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
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
use letswifi\LetsWifiConfig;
use letswifi\auth\User;
use letswifi\provider\Provider;
use letswifi\provider\Realm;

class UserCredentialManager
{
	public const DATE_FORMAT = 'Y-m-d H:i:s';

	public readonly Realm $realm;

	public function __construct(
		public readonly User $user,
		Realm|string $realm,
		public readonly Provider $provider,
		private readonly LetsWifiConfig $config,
		private readonly PDO $pdo,
		protected readonly DateTimeImmutable $now = new DateTimeImmutable(),
	) {
		// Ensure that the realm is one that is available for this user
		$this->realm = $user->getRealm( \is_string( $realm ) ? $realm : $realm->realmId );

		// The following check should already have happened when $provider->getAuthenticatedUser() was called
		\assert( $user->canUseRealm( $this->realm ) );
		\assert( $provider->hasRealm( $this->realm ) );
	}

	/**
	 * Issue a new credential
	 *
	 * @psalm-suppress InvalidReturnType Psalm doesn't understand that T is any subclass from Credential (PSALMBUG)
	 * @psalm-suppress InvalidReturnStatement Psalm doesn't understand that T is any subclass from Credential (PSALMBUG)
	 *
	 * @psalm-template T of Credential
	 *
	 * @psalm-param class-string<T> $credentialClass
	 *
	 * @param string $credentialClass The device or platform to generate a profile for
	 *
	 * @psalm-return T
	 */
	public function issue( string $credentialClass ): Credential
	{
		switch ( $credentialClass ) {
			case CertificateCredential::class: return $this->issueCertificateCredential();
		}

		throw new DomainException( 'Unable to issue a credential of class ' . $credentialClass );
	}

	/**
	 * @suppress PhanPossiblyNonClassMethodCall Phan doesn't understand PDO
	 * @suppress PhanPossiblyFalseTypeArgumentInternal Assume getTimestamp() doesn't return false
	 */
	private function logPreparedUserCredential( X509 $caCert, CSR $csr, DateTimeInterface $expiry, string $usage ): int
	{
		$csrData = $csr->getCSRPem();
		$statement = $this->pdo->prepare( 'INSERT INTO `realm_signing_log` (`realm`, `ca_sub`, `requester`, `usage`, `sub`, `issued`, `expires`, `csr`, `client`, `user_agent`, `ip`) VALUES (:realm, :ca_sub, :requester, :usage, :sub, :issued, :expires, :csr, :client, :user_agent, :ip)' );
		$statement->bindValue( 'realm', $this->realm->realmId, PDO::PARAM_STR );
		$statement->bindValue( 'ca_sub', $caCert->getSubject(), PDO::PARAM_STR );
		$statement->bindValue( 'requester', $this->user->userId, PDO::PARAM_STR );
		$statement->bindValue( 'usage', $usage, PDO::PARAM_STR );
		$statement->bindValue( 'sub', $csr->getSubject(), PDO::PARAM_STR );
		$statement->bindValue( 'issued', \gmdate( static::DATE_FORMAT, $this->now->getTimestamp() ), PDO::PARAM_STR );
		$statement->bindValue( 'expires', \gmdate( static::DATE_FORMAT, $expiry->getTimestamp() ), PDO::PARAM_STR );
		$statement->bindValue( 'csr', $csrData, PDO::PARAM_STR );
		$statement->bindValue( 'client', $this->user->clientId, PDO::PARAM_STR );
		$statement->bindValue( 'user_agent', $this->user->userAgent, PDO::PARAM_STR );
		$statement->bindValue( 'ip', $this->user->ip, PDO::PARAM_STR );
		$statement->execute();
		$last = $this->pdo->lastInsertId();
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
		$statement = $this->pdo->prepare( 'UPDATE `realm_signing_log` SET `issued` = :issued, `expires` = :expires, `x509` = :x509 WHERE `serial` = :serial AND `realm` = :realm AND `requester` = :requester AND `usage` = :usage AND `ca_sub` = :ca_sub' );
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
		$commonName = static::createCommonName( '@' . \rawurlencode( $this->realm->realmId ) );
		$dn = new DN( ['CN' => $commonName] );
		$csr = CSR::generate( $dn, $userKey );
		$signerDN = $this->config->getRealmData( $this->realm->realmId )['signer'];
		$caCert = new X509( $this->config->getCertificateData( $signerDN )['x509'] );
		$serial = $this->logPreparedUserCredential( $caCert, $csr, $expiry, 'client' );

		$signerData = $this->config->getCertificateData( $signerDN );
		$caKey = new PrivateKey( $signerData['key'], $signerData['passphrase'] ?? null );
		$conf = new OpenSSLConfig( x509Extensions: OpenSSLConfig::X509_EXTENSION_CLIENT );
		$userCert = $csr->sign( $caCert, $caKey, $expiry, $conf, $serial );
		$this->logCompletedUserCredential( $userCert, 'client' );

		return new PKCS12( $userCert, $userKey, [$caCert] );
	}

	private function issueCertificateCredential(): CertificateCredential
	{
		return new CertificateCredential( $this->user, $this->realm, $this->provider, fn() => $this->generateClientCertificate() );
	}

	private static function createCommonName( string $realm ): string
	{
		return \substr( \strtolower( \strtr( \base64_encode( \random_bytes( 12 ) ), '/+9876', '012345' ) ), 0, 64 - \strlen( $realm ) ) . $realm;
	}
}
