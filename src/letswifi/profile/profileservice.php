<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\profile;

use PDO;
use fyrkat\openssl\PrivateKey;
use fyrkat\openssl\X509;
use letswifi\configuration\Dictionary;
use letswifi\error\MisdirectException;

class ProfileService
{
	private ?PDO $pdo = null;

	public function __construct( private readonly Dictionary $config, public string $httpHost )
	{
	}

	public function getProvider(): Provider
	{
		return Provider::fromConfig( $this, $this->getProviderDictionary() );
	}

	public function getContact( string $contactId ): Contact
	{
		return Contact::fromConfig( $this->config->getDictionary( 'contact' )->getDictionary( $contactId ) );
	}

	public function getRealm( string $realmId ): Realm
	{
		return Realm::fromConfig( $this, $this->config->getDictionary( 'realm' )->getDictionary( $realmId ) );
	}

	/**
	 * Get a certificate by it's subject, including the chain up to (including) the root
	 *
	 * This function filters away the private key, disallowing consumers of this class
	 * to sign certificates or leak the key.  In order to get access to the private keys,
	 * direct access to the inner $this->config, as provided in the constructor, is needed.
	 *
	 * @return array<X509>
	 */
	public function getCertificatesWithChain( string ...$sub ): array
	{
		$result = [];
		$subjects = [];
		$certificates = $this->config->getDictionary( 'certificate' );
		foreach ( $sub as $subject ) {
			while ( $subject ) {
				$certificateData = $certificates->getDictionary( $subject );
				if ( !\in_array( $subject, $subjects, true ) ) {
					\array_unshift( $result, new X509( $certificateData->getString( 'x509' ) ) );
				}
				$subjects[] = $subject;
				$subject = $certificateData->getStringOrNull( 'issuer' );
			}
		}

		return $result;
	}

	public function getCertificate( string $sub ): X509
	{
		$certificates = $this->config->getDictionary( 'certificate' );
		$certificateData = $certificates->getDictionary( $sub );

		return new X509( $certificateData->getString( 'x509' ) );
	}

	public function getPrivateKey( string $sub ): PrivateKey
	{
		$certificates = $this->config->getDictionary( 'certificate' );
		$certificateData = $certificates->getDictionary( $sub );

		return new PrivateKey( $certificateData->getString( 'key' ), $certificateData->getStringOrNull( 'passphrase' ) );
	}

	/**
	 * @return array<Network>
	 */
	public function getNetworks( string ...$networks ): array
	{
		$networkData = $this->config->getDictionary( 'network' );

		return \array_merge( ...\array_values( \array_map(
			static fn( string $n ) => Network::allFromConfig( $networkData->getDictionary( $n ) ),
			$networks,
		) ) );
	}

	public function getPDO(): PDO
	{
		if ( null !== $this->pdo ) {
			return $this->pdo;
		}
		$providerData = $this->getProviderDictionary();
		$pdoData = $providerData->getDictionary( 'pdo' );
		$dsn = $pdoData->getString( 'dsn' );
		$username = $pdoData->getStringOrNull( 'username' );
		$password = $pdoData->getStringOrNull( 'password' );

		$pdo = new PDO( $dsn, $username, $password );
		$pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

		if ( \strstr( $dsn, ':', true ) === 'mysql' ) {
			// https://dev.mysql.com/doc/refman/8.4/en/set-variable.html
			// https://dev.mysql.com/doc/refman/8.4/en/sql-mode.html#sqlmode_ansi_quotes
			// TODO do we override existing modes this way, do we want to keep them, how?
			$pdo->exec( 'SET SESSION sql_mode = \'ANSI_QUOTES\';' );
		}

		return $this->pdo = $pdo;
	}

	private function getProviderDictionary(): Dictionary
	{
		$allProviders = $this->config->getDictionary( 'provider' );

		return $allProviders->getDictionaryOrNull( $this->httpHost )
			?? $allProviders->getDictionaryOrNull( '_default' )
			?? throw new MisdirectException( $this->httpHost );
	}
}
