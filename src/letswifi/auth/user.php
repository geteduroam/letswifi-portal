<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\auth;

use DomainException;
use letswifi\provider\Realm;

class User
{
	/**
	 * @param array<string,Realm> $realms
	 * @param array<string>       $affiliations
	 */
	public function __construct(
		public readonly string $userId,
		public readonly array $realms,
		public readonly array $affiliations,
		public readonly ?string $clientId = null,
		public readonly ?string $ip = null,
		public readonly ?string $userAgent = null,
	) {
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

			throw new DomainException( 'No default realm is available for the current user' );
		}
		if ( \array_key_exists( $realmId, $this->realms ) ) {
			return $this->realms[$realmId];
		}

		throw new DomainException( "Realm {$realmId} is not available for the current user" );
	}
}
