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
use letswifi\error\ForbiddenException;
use letswifi\error\RealmMismatchException;
use letswifi\profile\Provider;
use letswifi\profile\Realm;

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

	public function isAdmin(): bool
	{
		return false;
	}

	public function jsonSerialize(): array
	{
		return \array_filter( [
			'user_id' => $this->userId,
			'admin' => $this->isAdmin(),
			'provider' => $this->provider->host,
			'realms' => \array_keys( $this->realms ),
			'affiliations' => $this->affiliations,
			'client_id' => $this->clientId,
			'grant_sid' => $this->grantSid,
			'ip' => $this->ip,
			'user_agent' => $this->userAgent,
		] );
	}

	/** @return array<string,Realm>*/
	public function getRealms(): array
	{
		return $this->realms;
	}

	/**
	 * @param string ...$affiliations All affiliations that will trigger a match
	 *
	 * @return bool At least one of our affiliations or the username matches the given affiliations
	 */
	public function hasAffiliations( string ...$affiliations ): bool
	{
		foreach ( $this->affiliations + [-1 => $this->userId] as $affiliation ) {
			if ( \in_array( $affiliation, $affiliations, true ) ) {
				return true;
			}
		}

		return false;
	}

	public function canUseRealm( string|Realm $realm ): bool
	{
		return \in_array(
			\is_string( $realm ) ? $realm : $realm->realmId,
			\array_map(
				static fn( Realm $r ) => $r->realmId,
				$this->getRealms() ),
			true );
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

	public function promote(): Admin
	{
		// Check that we are not admin already;
		// it's not too bad if this happens in
		\assert( !$this->isAdmin(), 'Attempted to promote admin to admin' );

		// TODO: Don't use affiliations for this, those are too broad
		// use a separate attribute list for admin access
		$realms = $this->hasAffiliations( ...$this->provider->admins )
			? $this->provider->allRealms()
			: \array_filter( $this->provider->allRealms(), fn( Realm $r ) => $this->hasAffiliations( ...$r->admins ) );
		if ( empty( $realms ) ) {
			throw new ForbiddenException( 'Attempted promotion to admin but user is not admin for any requested realm' );
		}

		return new Admin(
			userId: $this->userId,
			provider: $this->provider,
			realms: $realms,
			affiliations: $this->affiliations,
			clientId: $this->clientId,
			grantSid: $this->grantSid,
			ip: $this->ip,
			userAgent: $this->userAgent,
		);
	}
}
