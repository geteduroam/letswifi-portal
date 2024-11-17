<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi;

class LetsWifiConfig extends Config
{
	public function getProviderData( string $httpHost ): Config
	{
		$providers = $this->getDictionary( 'providers' );

		return $providers->getDictionaryOrNull( $httpHost ) ?? $providers->getDictionary( '*' );
	}

	public function getContactData( string $contactId ): Config
	{
		return $this->getDictionary( 'contact' )->getDictionary( "{$contactId}" );
	}

	public function getRealmData( string $realmId ): Config
	{
		return $this->getDictionary( 'realm' )->getDictionary( $realmId );
	}

	public function getCertificateData( string $sub ): Config
	{
		return $this->getDictionary( 'certificate' )->getDictionary( $sub );
	}

	public function getNetworkData( string $network ): Config
	{
		return $this->getDictionary( 'network' )->getDictionary( $network );
	}
}
