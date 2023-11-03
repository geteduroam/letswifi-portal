<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2022, Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: 2020-2022, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\realm;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use fyrkat\openssl\CSR;
use fyrkat\openssl\DN;
use fyrkat\openssl\OpenSSLConfig;
use fyrkat\openssl\PKCS12;
use fyrkat\openssl\PrivateKey;
use fyrkat\openssl\X509;
use InvalidArgumentException;

use letswifi\profile\auth\TlsAuth;

use letswifi\profile\EduroamProfileData;
use letswifi\profile\generator\Generator;

use letswifi\profile\IProfileData;

class Realm
{
	/** @var string */
	private $name;

	/** @var RealmManager */
	private $manager;

	/** @var ?array<string,string> */
	private $data;

	public function __construct( RealmManager $manager, string $name )
	{
		$this->manager = $manager;
		$this->name = $name;
	}

	/**
	 * @psalm-template T of Generator
	 *
	 * @psalm-param class-string<T> $generator The config generator to return
	 *
	 * @param string        $generator  The config generator class to return
	 * @param User          $user
	 * @param ?string       $passphrase Passphrase to encrypt the profile with
	 * @param ?DateInterval $validity   Period the profile will be valid for from generation
	 *
	 * @psalm-return T
	 */
	public function getConfigGenerator( string $generator, User $user, ?string $passphrase = null, ?DateInterval $validity = null ): Generator
	{
		if ( null === $validity ) {
			$validity = $this->manager->getDefaultValidity( $this->name );
		}
		$expiry = (new DateTimeImmutable())->add( $validity );
		// TODO check that $expiry is not too far in the future,
		//	during some test we ended up with 88363-05-14 and MySQL didn't like
		// TODO more generic method to get an arbitrary generator
		$pkcs12 = $this->generateClientCertificate( $user, $expiry );

		return new $generator( $this->getProfileData(), [$this->createAuthenticationMethod( $pkcs12 )], $passphrase );
	}

	/**
	 * @return array<X509>
	 */
	public function getTrustedCaCertificates(): array
	{
		/** @var array<X509> */
		$result = [];
		foreach ( $this->manager->getTrustedCas( $this->name ) as $ca ) {
			/** @var array<X509> */
			$subResult = [];
			do {
				$subResult[] = $ca->getX509();
				$ca = $ca->getIssuerCA();
			} while ( null !== $ca );
			// Reverse the certificates so we have the same order as CAT
			for ( $i = \count( $subResult ) - 1; 0 <= $i; --$i ) {
				$result[] = $subResult[$i];
			}
		}

		return $result;
	}

	/**
	 * @param User              $requester  User requesting the certificate
	 * @param string            $commonName Common name of the server certificate, must be a hostname
	 * @param DateTimeInterface $expiry     Expiry date
	 */
	public function generateServerCertificate( User $requester, string $commonName, DateTimeInterface $expiry ): PKCS12
	{
		if ( !\filter_var( $commonName, \FILTER_VALIDATE_DOMAIN | \FILTER_NULL_ON_FAILURE ) ) {
			throw new InvalidArgumentException( 'Common name for a server certificate must be a hostname' );
		}
		$serverKey = new PrivateKey( new OpenSSLConfig( OpenSSLConfig::KEY_EC ) );
		$dn = new DN( ['CN' => $commonName] );
		$csr = CSR::generate( $dn, $serverKey );
		$caCert = $this->getSigningCACertificate();
		$serial = $this->logPreparedServerCredential( $caCert, $requester, $csr, $expiry );

		$caKey = $this->getSigningCAKey();
		$conf = new OpenSSLConfig( OpenSSLConfig::X509_SERVER + ['san' => 'DNS:' . $commonName] );
		$serverCert = $csr->sign( $caCert, $caKey, $expiry, $conf, $serial );
		$this->logCompletedServerCredential( $requester, $serverCert );

		return new PKCS12( $serverCert, $serverKey, [$caCert] );
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	public function getTrustedServerNames(): array
	{
		return $this->manager->getServerNames( $this->name );
	}

	public function getProfileData(): IProfileData
	{
		// TODO add helpdesk info, logo and such
		return new EduroamProfileData( $this->getName() );
	}

	public function getSigningCACertificate(): X509
	{
		return $this->manager->getSignerCa( $this->name )->getX509();
	}

	public function getSecretKey(): string
	{
		return $this->manager->getCurrentOAuthKey( $this->name );
	}

	public function getSigningCAKey(): PrivateKey
	{
		return $this->manager->getSignerCa( $this->name )->getPrivateKey();
	}

	protected function createAuthenticationMethod( PKCS12 $pkcs12 ): TlsAuth
	{
		$caCertificates = $this->getTrustedCaCertificates();
		$serverNames = $this->getTrustedServerNames();

		return new TlsAuth( $caCertificates, $serverNames, $pkcs12 );
	}

	protected function logPreparedUserCredential( X509 $caCert, User $requester, CSR $csr, DateTimeInterface $expiry ): int
	{
		return $this->manager->logPreparedCredential( $this->name, $caCert, $requester, $csr, $expiry, 'client' );
	}

	protected function logPreparedServerCredential( X509 $caCert, User $requester, CSR $csr, DateTimeInterface $expiry ): int
	{
		return $this->manager->logPreparedCredential( $this->name, $caCert, $requester, $csr, $expiry, 'server' );
	}

	protected function logCompletedUserCredential( User $user, X509 $userCert ): void
	{
		$this->manager->logCompletedCredential( $this->name, $user, $userCert, 'client' );
	}

	protected function logCompletedServerCredential( User $user, X509 $serverCert ): void
	{
		$this->manager->logCompletedCredential( $this->name, $user, $serverCert, 'server' );
	}

	protected function generateClientCertificate( User $user, DateTimeInterface $expiry ): PKCS12
	{
		$userKey = new PrivateKey( new OpenSSLConfig( OpenSSLConfig::KEY_RSA ) );
		$commonName = static::createCommonName( '@' . \rawurlencode( $user->getRealm() ) );
		$dn = new DN( ['CN' => $commonName] );
		$csr = CSR::generate( $dn, $userKey );
		$caCert = $this->getSigningCACertificate();
		$serial = $this->logPreparedUserCredential( $caCert, $user, $csr, $expiry );

		$caKey = $this->getSigningCAKey();
		$conf = new OpenSSLConfig( OpenSSLConfig::X509_CLIENT );
		$userCert = $csr->sign( $caCert, $caKey, $expiry, $conf, $serial );
		$this->logCompletedUserCredential( $user, $userCert );

		return new PKCS12( $userCert, $userKey, [$caCert] );
	}

	private static function createCommonName( string $realm ): string
	{
		return \substr( \strtolower( \strtr( \base64_encode( \random_bytes( 12 ) ), '/+9876', '012345' ) ), 0, 64 - \strlen( $realm ) ) . $realm;
	}
}
