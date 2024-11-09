<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\provider;

class Location
{
	public function __construct( public readonly float $lat, public readonly float $lon )
	{
	}

	public static function fromArray( array $location ): self
	{
		return new self( lat: $location['lat'], lon: $location['lon'] );
	}
}
