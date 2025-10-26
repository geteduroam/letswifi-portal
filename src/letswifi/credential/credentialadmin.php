<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\credential;

use DateTimeImmutable;
use DateTimeInterface;
use DomainException;
use Generator;
use letswifi\auth\Admin;
use letswifi\configuration\Dictionary;
use letswifi\profile\Provider;
use letswifi\profile\Realm;

abstract class CredentialAdmin
{
	public function __construct(
		public readonly Admin $admin,
		public readonly Provider $provider,
		protected readonly Dictionary $config,
		public readonly DateTimeImmutable $now = new DateTimeImmutable(),
	) {
		// TODO: Do we really need the provider here,
		// it's already in the user object which we should be able to trust here
		if ( $this->provider->host !== $this->admin->provider->host ) {
			throw new DomainException( 'The provider must match the user provider' );
		}
	}

	/**
	 * @param array<Realm|string> $realms
	 * @param DateTimeInterface   $validOn   Consider credentials that are valid on this point in time
	 * @param ?string             $requester Filter requester
	 *
	 * @return Generator<string,RequesterAggregate>
	 */
	abstract public function listRequesters( array $realms = [], ?DateTimeInterface $validOn = null, ?string $requester = null ): Generator;

	abstract public function listCredentials( array $realms = [], ?DateTimeInterface $validOn = null, ?string $requester = null ): Generator;

	abstract public function revokeCredential( string $credentialId, ?string $requester = null ): void;

	abstract public function revokeRequester( ?string $requester, ?DateTimeInterface $validOn = null, string|Realm|null $realm = null ): void;
}
