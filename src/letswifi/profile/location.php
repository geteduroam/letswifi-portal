<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2021, Jørn Åne de Jong, Uninett AS <jornane.dejong@surf.nl>
 * Copyright: 2020-2021, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\profile;

class Location
{
	/** @var float */
	private $lat;

	/** @var float */
	private $lon;

	public function __construct( float $lat, float $lon )
	{
		$this->lat = $lat;
		$this->lon = $lon;
	}

	public function getLatitude(): float
	{
		return $this->lat;
	}

	public function getLongitude(): float
	{
		return $this->lon;
	}
}
