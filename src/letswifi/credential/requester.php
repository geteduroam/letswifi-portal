<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\credential;

use Closure;
use DomainException;
use JsonSerializable;

class Requester implements JsonSerializable
{
	/** @internal */
	public function __construct(
		public readonly string $name,
		public readonly string $realm,
		protected readonly ?Closure $revoke = null,
	) {
	}

	public function revoke(): void
	{
		$f = $this->revoke;
		if ( null === $f ) {
			throw new DomainException( 'Aggregated credential cannot be revoked' );
		}
		$f();
	}

	public function jsonSerialize(): array
	{
		return [
			'name' => $this->name,
			'realm' => $this->realm,
		];
	}
}
