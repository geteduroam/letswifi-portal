<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi;

use DomainException;

class LetsWifiConfig extends Config
{
	/**
	 * @return array{}
	 */
	public function getProviderData( string $httpHost ): array
	{
		$providers = $this->getArray( 'providers' );
		if ( \array_key_exists( $httpHost, $providers ) ) {
			return $providers[$httpHost] + ['host' => $httpHost];
		}
		if ( \array_key_exists( '*', $providers ) ) {
			return $providers['*'] + ['host' => '*'];
		}

		throw new DomainException( 'Cannot get provider for this hostname' );
	}

	public function getContactData( string $contactId ): array
	{
		return $this->getArray( 'contact' )[$contactId] ?? throw new DomainException( 'Cannot get contact information for ' . $contactId );
	}

	/**
	 * @return array{realm_id:string,display_name:string,description:string,server_names:array<string>,signer:string,trust:array<string>,validity:int|string,contact:string}
	 */
	public function getRealmData( string $realmId ): array
	{
		return ( $this->getArray( 'realm' )[$realmId] ?? throw new DomainException( 'Cannot get realm information ' . $realmId ) ) + ['realm_id' => $realmId];
	}

	/**
	 * @return array{x509:string,key:string,passphrase?:?string,issuer?:?string}
	 */
	public function getCertificateData( string $sub ): array
	{
		return $this->getArray( 'certificate' )[$sub];
	}

	/**
	 * @return array{network_id:string,display_name:string,oid?:array<string>,nai?:array<string>,ssid?:string}
	 */
	public function getNetworkData( string $network ): array
	{
		return $this->getArray( 'network' )[$network] + ['network_id' => $network];
	}
}
