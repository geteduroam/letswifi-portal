<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\tenant;

use fyrkat\multilang\MultiLanguageString;
use letswifi\configuration\Dictionary;

class NetworkSSID extends Network
{
	public function __construct(
		string $networkId,
		MultiLanguageString $displayName,
		public readonly string $ssid )
	{
		parent::__construct( networkId: $networkId, displayName: $displayName );
	}

	/**
	 * @return array{network_id:string,display_name:MultiLanguageString,ssid:string}
	 */
	public function jsonSerialize(): array
	{
		return [
			'network_id' => $this->networkId,
			'display_name' => $this->displayName,
			'ssid' => $this->ssid,
		];
	}

	public static function fromConfig( Dictionary $networkConfig ): self
	{
		return new self(
			networkId: $networkConfig->getParentKey(),
			displayName: $networkConfig->getMultiLanguageString( 'display_name' ),
			ssid: $networkConfig->getString( 'ssid' ),
		);
	}
}
