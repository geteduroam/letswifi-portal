<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2020, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi;

use DateTimeInterface;
use DomainException;

use fyrkat\openssl\CSR;
use fyrkat\openssl\DN;
use fyrkat\openssl\OpenSSLConfig;
use fyrkat\openssl\PKCS12;
use fyrkat\openssl\PrivateKey;
use fyrkat\openssl\X509;

use letswifi\EapConfig\Auth\TlsAuthenticationMethod;
use letswifi\EapConfig\EapConfigGenerator;
use letswifi\EapConfig\Profile\EduroamProfileData;
use letswifi\EapConfig\Profile\IProfileData;

use PDO;

class Realm
{
	/** @var PDO */
	private $pdo;

	/** @var string */
	private $name;

	/** @var ?string */
	private $signingPassphrase;

	/** @var ?array<string,string> */
	private $data;

	public function __construct( PDO $pdo, string $name, ?string $signingPassphrase = null )
	{
		$pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		$this->pdo = $pdo;
		$this->name = $name;
		$this->signingPassphrase = $signingPassphrase;
	}

	/**
	 * @param User $user
	 *
	 * @return EapConfigGenerator
	 */
	public function getUserEapConfig( User $user, DateTimeInterface $expiry ): EapConfigGenerator
	{
		$pkcs12 = $this->generateClientCertificate( $user, $expiry );
		$anonymousIdentity = $user->getAnonymousUsername();

		return new EapConfigGenerator( $this->getProfileData(), [$this->createAuthenticationMethod( $pkcs12, $anonymousIdentity )] );
	}

	/**
	 * @return array<X509>
	 */
	public function getTrustedCaCertificates(): array
	{
		return \array_map( 'letswifi\\Realm::createX509', \explode( ';', $this->getRealmData( 'trustedCaCert' ) ) );
	}

	public function getSecretKey(): string
	{
		return $this->getRealmData( 'secretKey' );
	}

	/**
	 * @suppress PhanPossiblyNonClassMethodCall
	 * @suppress PhanPluginUseReturnValueInternalKnown
	 */
	public function writeRealmData( array $data ): void
	{
		$statement = $this->pdo->prepare( 'REPLACE INTO realm (domain, trustedCaCert, trustedServerName, signingCaCert, signingCaKey, secretKey) VALUES (:domain, :trustedCaCert, :trustedServerName, :signingCaCert, :signingCaKey, :secretKey)' );
		$statement->bindValue( 'domain', $this->getName(), PDO::PARAM_STR );
		$statement->bindValue( 'trustedCaCert', $data['trustedCaCert'], PDO::PARAM_STR );
		$statement->bindValue( 'trustedServerName', $data['trustedServerName'], PDO::PARAM_STR );
		$statement->bindValue( 'signingCaCert', $data['signingCaCert'], PDO::PARAM_STR );
		$statement->bindValue( 'signingCaKey', $data['signingCaKey'], PDO::PARAM_STR );
		$statement->bindValue( 'secretKey', $data['secretKey'], PDO::PARAM_STR );
		$statement->execute();
		$rows = $statement->rowCount();
		if ( 1 !== $rows ) {
			throw new DomainException( "Unable to create realm; expected 1 row to be updated but got ${rows}" );
		}
	}

	/**
	 * @param User              $requester  User requesting the certificate
	 * @param string            $commonName Common name of the server certificate
	 * @param DateTimeInterface $expiry     Validity in days
	 */
	public function generateServerCertificate( User $requester, string $commonName, DateTimeInterface $expiry ): PKCS12
	{
		$serverKey = new PrivateKey( new OpenSSLConfig( OpenSSLConfig::KEY_EC ) );
		$dn = new DN( ['CN' => $commonName] );
		$csr = CSR::generate( $dn, $serverKey );
		$serial = $this->logPreparedUserCredential( $requester, $csr, $expiry );

		$caCert = $this->getSigningCACertificate();
		$caKey = $this->getSigningCAKey();
		$conf = new OpenSSLConfig( OpenSSLConfig::X509_SERVER );
		$serverCert = $csr->sign( $caCert, $caKey, $expiry, $conf, $serial );
		$this->logCompletedUserCredential( $requester, $serverCert );

		return new PKCS12( $serverCert, $serverKey, [$caCert] );
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	protected function getTrustedServerNames(): array
	{
		return \explode( ';', $this->getRealmData('trustedServerName' ) );
	}

	protected function createAuthenticationMethod( PKCS12 $pkcs12, string $anonymousIdentity ): TlsAuthenticationMethod
	{
		$caCertificates = $this->getTrustedCaCertificates();
		$serverNames = $this->getTrustedServerNames();
		$anonymousIdentity = \rawurldecode( \strstr( $anonymousIdentity, '@', true ) ?: $anonymousIdentity ) . '@' . \rawurldecode( $this->getName() );

		return new TlsAuthenticationMethod( $caCertificates, $serverNames, $anonymousIdentity, $pkcs12 );
	}

	protected function logPreparedUserCredential( User $requester, CSR $csr, DateTimeInterface $expiry ): int
	{
		return $this->logPreparedCredential( $requester, $csr, $expiry, 'client' );
	}

	protected function logPreparedServerCredential( User $requester, CSR $csr, DateTimeInterface $expiry ): int
	{
		return $this->logPreparedCredential( $requester, $csr, $expiry, 'server' );
	}

	/**
	 * @suppress PhanPossiblyNonClassMethodCall
	 * @suppress PhanPluginUseReturnValueInternalKnown
	 */
	protected function logPreparedCredential( User $requester, CSR $csr, DateTimeInterface $expiry, string $usage ): int
	{
		$csrData = $csr->getCSRPem();
		$statement = $this->pdo->prepare( 'INSERT INTO tlsindex (domain, requester, usage, commonName, startDate, endDate, csr) VALUES (:domain, :requester, :usage, :commonName, :startDate, :endDate, :csr)' );
		$statement->bindValue( 'domain', $this->getName(), PDO::PARAM_STR );
		$statement->bindValue( 'requester', $requester->getUserID(), PDO::PARAM_STR );
		$statement->bindValue( 'usage', $usage, PDO::PARAM_STR );
		$statement->bindValue( 'commonName', $csr->getSubject(), PDO::PARAM_STR );
		$statement->bindValue( 'startDate', \date( 'Y-m-d' ), PDO::PARAM_STR );
		$statement->bindValue( 'endDate', $expiry->format( 'Y-m-d' ), PDO::PARAM_STR );
		$statement->bindValue( 'csr', $csrData, PDO::PARAM_STR );
		$statement->execute();
		$last = $this->pdo->lastInsertId();
		$lastId = (int)$last;
		if ( $lastId > 0 && (string)$lastId === $last ) {
			return $lastId;
		}
		throw new DomainException( 'Unable to retrieve last insert ID from database' );
	}

	protected function logCompletedUserCredential( User $user, X509 $userCert ): void
	{
		$this->logCompletedCredential( $user, $userCert, 'client' );
	}

	protected function logCompletedServerCredential( User $user, X509 $userCert ): void
	{
		$this->logCompletedCredential( $user, $userCert, 'server' );
	}

	/**
	 * @suppress PhanPossiblyNonClassMethodCall
	 * @suppress PhanPluginUseReturnValueInternalKnown
	 */
	protected function logCompletedCredential( User $user, X509 $userCert, string $usage ): void
	{
		$statement = $this->pdo->prepare( 'UPDATE tlsindex SET startDate = :startDate, endDate = :endDate, x509 = :x509 WHERE serial = :serial AND domain = :domain AND requester = :requester AND usage = :usage' );
		$statement->bindValue( 'startDate', $userCert->getValidFrom()->format( 'Y-m-d' ), PDO::PARAM_STR );
		$statement->bindValue( 'endDate', $userCert->getValidTo()->format( 'Y-m-d' ), PDO::PARAM_STR );
		$statement->bindValue( 'x509', $userCert->getX509Pem(), PDO::PARAM_STR );
		$statement->bindValue( 'serial', $userCert->getSerialNumber(), PDO::PARAM_INT );
		$statement->bindValue( 'domain', $this->getName(), PDO::PARAM_STR );
		$statement->bindValue( 'requester', $user->getUserID(), PDO::PARAM_STR );
		$statement->bindValue( 'usage', $usage, PDO::PARAM_STR );
		$statement->execute();
		$rows = $statement->rowCount();
		if ( 1 !== $rows ) {
			throw new DomainException( "Unable to log signed certificate; expected 1 rows to be updated but got ${rows}" );
		}
	}

	protected function getProfileData(): IProfileData
	{
		// TODO add helpdesk info, logo and such
		return new EduroamProfileData( $this->getName() );
	}

	/**
	 * @suppress PhanPossiblyNonClassMethodCall
	 * @suppress PhanPluginUseReturnValueInternalKnown
	 */
	protected function getRealmData( string $field ): string
	{
		if ( null === $this->data ) {
			$statement = $this->pdo->prepare( 'SELECT domain, trustedCaCert, trustedServerName, signingCaCert, signingCaKey, secretKey FROM realm WHERE domain = ?' );
			$statement->execute( [$this->name] );
			$realmData = $statement->fetch();
			if ( !$realmData ) {
				throw new DomainException( "Unable to find realm {$this->name}", 404 );
			}
			$this->data = $realmData;
		}
		if ( !\array_key_exists( $field, $this->data ) ) {
			throw new DomainException( "No realm field ${field} exists" );
		}
		if ( \is_string( $this->data[$field] ) ) {
			return $this->data[$field];
		}
		throw new DomainException( "Expected field {$field} to be string but was " . \gettype( $this->data[$field] ) );
	}

	protected function generateClientCertificate( User $user, DateTimeInterface $expiry ): PKCS12
	{
		$userKey = new PrivateKey( new OpenSSLConfig( OpenSSLConfig::KEY_EC ) );
		$commonName = static::createUUID() . '@' . \rawurlencode( $this->getName() );
		$dn = new DN( ['CN' => $commonName] );
		$csr = CSR::generate( $dn, $userKey );
		$serial = $this->logPreparedUserCredential( $user, $csr, $expiry );

		$caCert = $this->getSigningCACertificate();
		$caKey = $this->getSigningCAKey();
		$conf = new OpenSSLConfig( OpenSSLConfig::X509_CLIENT );
		$userCert = $csr->sign( $caCert, $caKey, $expiry, $conf, $serial );
		$this->logCompletedUserCredential( $user, $userCert );

		return new PKCS12( $userCert, $userKey, [$caCert] );
	}

	protected function getSigningCACertificate(): X509
	{
		return new X509( $this->getRealmData( 'signingCaCert' ) );
	}

	protected function getSigningCAKey(): PrivateKey
	{
		return new PrivateKey( $this->getRealmData( 'signingCaKey' ), $this->signingPassphrase );
	}

	private static function createX509( string $c ): X509
	{
		return new X509( $c );
	}

	private function createUUID(): string
	{
		$bytes = \random_bytes( 16 );
		$bytes[6] = \chr( \ord( $bytes[6] ) & 0x0F | 0x40 );
		$bytes[8] = \chr( \ord( $bytes[8] ) & 0x3F | 0x40 );

		return \bin2hex( $bytes[0] . $bytes[1] . $bytes[2] . $bytes[3] )
			. '-' . \bin2hex( $bytes[4] . $bytes[5] )
			. '-' . \bin2hex( $bytes[6] . $bytes[7] )
			. '-' . \bin2hex( $bytes[8] . $bytes[9] )
			. '-' . \bin2hex( $bytes[10] . $bytes[11] . $bytes[12] . $bytes[13] . $bytes[14] . $bytes[15] )
			;
	}
}
