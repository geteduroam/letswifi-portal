<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2020, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
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
