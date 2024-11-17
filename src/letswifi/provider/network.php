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
use letswifi\Config;

abstract class Network
{
	public function __construct( public readonly string $networkId, public readonly string $displayName )
	{
	}

	/**
	 * @return array<Network>
	 */
	public static function allFromConfig( Config $networkConfig ): array
	{
		$result = [];
		if ( null !== $networkConfig->getStringOrNull( 'ssid' ) ) {
			$result[] = NetworkSSID::fromConfig( $networkConfig );
		} elseif ( null !== $networkConfig->getStringOrNull( 'oid' ) ) {
			$result[] = NetworkPasspoint::fromConfig( $networkConfig );
		} else {
			throw new DomainException( \sprintf( 'Incomplete network %s', $networkConfig->getParentKey() ) );
		}

		return $result;
	}
}
