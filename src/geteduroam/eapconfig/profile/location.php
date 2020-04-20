<?php declare(strict_types=1);

/*
 * This file is part of geteduroam; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2020, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace geteduroam\EapConfig\Profile;

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

	public function generateEapConfigXml(): string
	{
		return ''
			. "\r\n<ProviderLocation>"
			. "\r\n<Longitude>{$this->lon}</Longitude>"
			. "\r\n<Latitude>{$this->lat}</Latitude>"
			. "\r\n</ProviderLocation>"
			;
	}
}
