<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\credential;

use DateTimeInterface;
use letswifi\auth\User;
use letswifi\provider\Provider;
use letswifi\provider\Realm;

abstract class Credential
{
	public function __construct(
		public readonly User $user,
		public readonly Realm $realm,
		public readonly Provider $provider,
	) {
		\assert( $user->canUseRealm( $this->realm ) );
		\assert( $provider->hasRealm( $this->realm ) );
	}

	abstract public function getExpiry(): ?DateTimeInterface;

	/*public function getIdentity(): string
	{
		return $this->user->userId . '@' . $this->realm->realmId;
	}*/
}
