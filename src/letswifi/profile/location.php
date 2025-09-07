<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\profile;

use JsonSerializable;
use letswifi\configuration\Dictionary;

class Location implements JsonSerializable
{
	public function __construct( public readonly float $lat, public readonly float $lon )
	{
	}

	/**
	 * @return array{lat:float,lon:float}
	 */
	public function jsonSerialize(): array
	{
		return ['lat' => $this->lat, 'lon' => $this->lon];
	}

	public static function fromConfig( Dictionary $location ): self
	{
		$lat = $location->getFloat( 'lat' );
		$lon = $location->getFloat( 'lon' );

		return new self( lat: $lat, lon: $lon );
	}
}
