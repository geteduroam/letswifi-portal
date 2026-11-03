<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\credential;

use JsonSerializable;

class Requester implements JsonSerializable
{
	/** @internal */
	public function __construct(
		public readonly string $name,
		public readonly string $realm,
	) {
	}

	public function jsonSerialize(): array
	{
		return [
			'name' => $this->name,
			'realm' => $this->realm,
		];
	}
}
