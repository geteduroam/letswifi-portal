<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\auth;

use JsonSerializable;
use letswifi\error\RealmMismatchException;
use letswifi\tenant\Provider;
use letswifi\tenant\Realm;

class User implements JsonSerializable
{
	/**
	 * @param array<string,Realm> $realms
	 * @param array<string>       $affiliations
	 */
	public function __construct(
		public readonly string $userId,
		public readonly Provider $provider,
		public readonly array $realms,
		public readonly array $affiliations,
		public readonly ?string $clientId = null,
		public readonly ?string $grantSid = null,
		public readonly ?string $ip = null,
		public readonly ?string $userAgent = null,
	) {
		foreach ( $realms as $realmId => $realm ) {
			\assert( $realmId === $realm->realmId );
			if ( !$provider->hasRealm( $realmId ) ) {
				throw new RealmMismatchException( $realm, user: $this, provider: $this->provider );
			}
		}
	}

	public function jsonSerialize(): array
	{
		return [
			'user_id' => $this->userId,
			'provider' => $this->provider->host,
			'realms' => \array_keys( $this->realms ),
			'affiliations' => $this->affiliations,
			'client_id' => $this->clientId,
			'grant_sid' => $this->grantSid,
			'ip' => $this->ip,
			'user_agent' => $this->userAgent,
		];
	}

	/** @return array<string,Realm>*/
	public function getRealms(): array
	{
		return $this->realms;
	}

	public function hasAffiliation( string $affiliation ): bool
	{
		return \in_array( $affiliation, $this->affiliations, true );
	}

	public function canUseRealm( Realm $realm ): bool
	{
		return \in_array( $realm->realmId, \array_map( static fn( Realm $r ) => $r->realmId, $this->getRealms() ), true );
	}

	public function getRealm( ?string $realmId = null ): Realm
	{
		if ( null === $realmId ) {
			if ( \count( $this->realms ) === 1 ) {
				foreach ( $this->realms as $realm ) {
					return $realm;
				}
			}

			throw new RealmMismatchException( provider: $this->provider );
		}

		if ( \array_key_exists( $realmId, $this->realms ) ) {
			return $this->realms[$realmId];
		}

		throw new RealmMismatchException( $realmId, user: $this, provider: $this->provider );
	}
}
