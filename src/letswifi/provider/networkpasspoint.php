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

class NetworkPasspoint extends Network
{
	/**
	 * @param array<string> $oids
	 * @param array<string> $naiRealms
	 */
	public function __construct(
		string $networkId,
		string $displayName,
		public readonly array $oids,
		public readonly array $naiRealms,
	) {
		parent::__construct( networkId: $networkId, displayName: $displayName );
	}

	public static function fromConfig( Config $networkConfig ): self
	{
		return new self(
			networkId: $networkConfig->getParentKey(),
			displayName: $networkConfig->getString( 'display_name' ),
			oids: $networkConfig->getList( 'oid' ),
			naiRealms: $networkConfig->getListOrEmpty( 'nai' ),
		);
	}
}
