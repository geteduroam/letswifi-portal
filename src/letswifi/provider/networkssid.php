<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\provider;

class NetworkSSID extends Network
{
	public function __construct(
		string $networkId,
		string $displayName,
		public readonly string $ssid )
	{
		parent::__construct( networkId: $networkId, displayName: $displayName );
	}

	/**
	 * @param array{network_id:string,display_name:string,ssid:string,...} $networkData
	 */
	public static function fromArray( array $networkData ): self
	{
		return new self(
			networkId: $networkData['network_id'],
			displayName: $networkData['display_name'],
			ssid: $networkData['ssid'],
		);
	}
}
