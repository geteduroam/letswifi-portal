<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\error;

use Throwable;
use letswifi\auth\User;
use letswifi\profile\Provider;
use letswifi\profile\Realm;

class RealmMismatchException extends ForbiddenException
{
	public function __construct(
		Realm|string|null $realm = null,
		?User $user = null,
		?Provider $provider = null,
		?Throwable $previous = null,
	) {
		parent::__construct( ( null === $realm
					? 'No default realm is available'
					: "Realm {$this->realmToString( $realm )} is not available"
		)
		. ( null === $user ? '' : " for user {$user->userId}" )
		. ( null === $provider ? '' : " at provider {$provider->host}" ),
			$previous );
	}

	private function realmToString( Realm|string $realm ): string
	{
		return $realm instanceof Realm ? $realm->realmId : $realm;
	}
}
