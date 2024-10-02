<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\profile\network;

class SSIDNetwork implements Network
{
	/** @var string */
	private $ssid;

	/** @var string */
	private $minRSNProto;

	public function __construct( string $ssid, string $minRSNProto = 'CCMP' )
	{
		$this->ssid = $ssid;
		$this->minRSNProto = $minRSNProto;
	}

	public function getSSID(): string
	{
		return $this->ssid;
	}

	public function getMinRSNProto(): string
	{
		return $this->minRSNProto;
	}
}
