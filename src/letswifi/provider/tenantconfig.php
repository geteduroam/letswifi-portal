<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\provider;

use fyrkat\openssl\X509;
use letswifi\LetsWifiConfig;

class TenantConfig
{
	public function __construct( private readonly LetsWifiConfig $config )
	{
	}

	public function getProvider( string $httpHost ): Provider
	{
		return Provider::fromArray( $this, $this->config->getProviderData( $httpHost ) );
	}

	public function getContact( string $contactId ): Contact
	{
		return Contact::fromArray( $this->config->getContactData( $contactId ) );
	}

	public function getRealm( string $realmId ): Realm
	{
		return Realm::fromArray( $this, $this->config->getRealmData( $realmId ) );
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
		foreach ( $sub as $subject ) {
			while ( $subject ) {
				$certificateData = $this->config->getCertificateData( $subject );
				if ( !\in_array( $subject, $subjects, true ) ) {
					\array_unshift( $result, new X509( $certificateData['x509'] ) );
				}
				$subjects[] = $subject;
				$subject = $certificateData['issuer'] ?? null;
			}
		}

		return $result;
	}

	/**
	 * @return array<Network>
	 */
	public function getNetworks( string ...$networks ): array
	{
		return \array_merge( ...\array_values( \array_map(
			fn( string $n ) => Network::allFromArray( $this->config->getNetworkData( $n ) ),
			$networks,
		) ) );
	}
}
