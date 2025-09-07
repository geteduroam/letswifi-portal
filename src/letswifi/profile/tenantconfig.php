<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\profile;

use fyrkat\openssl\X509;
use letswifi\LetsWifiConfig;
use letswifi\configuration\Dictionary;
use letswifi\error\MisdirectException;

class ProfileConfig
{
	public function __construct( private readonly Dictionary $config )
	{
	}

	public function getProvider( string $httpHost ): Provider
	{
		$providers = $this->config->getDictionary( 'provider' );

		return Provider::fromConfig( $this, $providers->getDictionaryOrNull( $httpHost )
			?? $providers->getDictionaryOrNull( '_default' )
			?? throw new MisdirectException( $httpHost ) );
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
	 * @see LetsWifiConfig#getCertificateData(string)
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
}
