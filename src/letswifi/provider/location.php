<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\provider;

use letswifi\Config;

class Location
{
	public function __construct( public readonly float $lat, public readonly float $lon )
	{
	}

	public static function fromConfig( Config $location ): ?self
	{
		$lat = $location->getNumericOrNull( 'lat' );
		$lon = $location->getNumericOrNull( 'lon' );
		if ( empty( "{$lat}{$lon}" ) ) {
			return null;
		}

		return new self( lat: (float)$lat, lon: (float)$lon );
	}
}
