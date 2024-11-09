<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\provider;

use DomainException;

abstract class Network
{
	public function __construct( public readonly string $networkId, public readonly string $displayName )
	{
	}

	/**
	 * @param array{network_id:string,display_name:string,oid?:array<string>,nai?:array<string>,ssid?:string} $networkData
	 *
	 * @return array<Network>
	 */
	public static function allFromArray( array $networkData ): array
	{
		$result = [];
		if ( \array_key_exists( 'ssid', $networkData ) ) {
			$result[] = NetworkSSID::fromArray( $networkData );
		} elseif ( \array_key_exists( 'oid', $networkData ) ) {
			$result[] = NetworkPasspoint::fromArray( $networkData );
		} else {
			throw new DomainException( "Incomplete network {$networkData['network_id']}" );
		}

		return $result;
	}
}
