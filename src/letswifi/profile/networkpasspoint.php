<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\profile;

use fyrkat\multilang\MultiLanguageString;
use letswifi\configuration\Dictionary;

class NetworkPasspoint extends Network
{
	/**
	 * @param array<string> $oids
	 * @param array<string> $naiRealms
	 */
	public function __construct(
		string $networkId,
		MultiLanguageString $displayName,
		public readonly array $oids,
		public readonly array $naiRealms,
	) {
		parent::__construct( networkId: $networkId, displayName: $displayName );
	}

	/**
	 * @return array{network_id:string,display_name:MultiLanguageString,oids:array<string>,nai_realms:array<string>}
	 */
	public function jsonSerialize(): array
	{
		return [
			'network_id' => $this->networkId,
			'display_name' => $this->displayName,
			'oids' => $this->oids,
			'nai_realms' => $this->naiRealms,
		];
	}

	public static function fromConfig( Dictionary $networkConfig ): self
	{
		return new self(
			networkId: $networkConfig->getParentKey(),
			displayName: $networkConfig->getMultiLanguageString( 'display_name' ),
			oids: $networkConfig->getRawArray( 'oid' ),
			naiRealms: $networkConfig->has( 'nai_realm' ) ? $networkConfig->getRawArray( 'nai_realm' ) : [],
		);
	}
}
