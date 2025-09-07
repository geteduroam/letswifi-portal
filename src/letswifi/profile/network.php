<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\profile;

use DomainException;
use JsonSerializable;
use fyrkat\multilang\MultiLanguageString;
use letswifi\configuration\Dictionary;

abstract class Network implements JsonSerializable
{
	public function __construct( public readonly string $networkId, public readonly MultiLanguageString $displayName )
	{
	}

	/**
	 * @return array<Network>
	 */
	public static function allFromConfig( Dictionary $networkConfig ): array
	{
		$result = [];
		if ( $networkConfig->has( 'ssid' ) ) {
			$result[] = NetworkSSID::fromConfig( $networkConfig );
		}
		if ( $networkConfig->has( 'oid' ) ) {
			$result[] = NetworkPasspoint::fromConfig( $networkConfig );
		}
		if ( empty( $result ) ) {
			throw new DomainException( \sprintf( 'Incomplete network %s', $networkConfig->getParentKey() ) );
		}

		return $result;
	}
}
