<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\provider;

use DomainException;
use JsonSerializable;
use letswifi\Config;
use letswifi\auth\AuthenticationContext;
use letswifi\auth\User;

class Provider implements JsonSerializable
{
	/**
	 * @param array<string,array<string>> $realmMap
	 */
	public function __construct(
		private readonly TenantConfig $tenantConfig,
		public readonly string $host,
		public readonly string $displayName,
		public readonly AuthenticationContext $auth,
		public readonly array $realmMap,
		public readonly ?string $contactId = null,
		public readonly ?string $description = null,
	) {
	}

	public function jsonSerialize(): array
	{
		return [
			'host' => $this->host,
			'displayName' => $this->displayName,
			'realmMap' => $this->realmMap,
			'contactId' => $this->contactId,
			'description' => $this->description,
		];
	}

	public static function fromConfig( TenantConfig $tenantConfig, Config $data ): self
	{
		$authData = $data->getDictionary( 'auth' );
		$authService = $authData->getString( 'service' );
		$oauth = $data->getDictionary( 'oauth' );
		$authServiceParams = $authData->getRawArray( 'param' );
		$auth = new AuthenticationContext( $authService, $authServiceParams, $oauth );

		return new self(
			tenantConfig: $tenantConfig,
			host: $data->getParentKey(),
			displayName: $data->getString( 'display_name' ),
			auth: $auth,
			realmMap: $data->getRawArray( 'realm' ),
			contactId: $data->getString( 'contact' ),
			description: $data->getString( 'description' ),
		);
	}

	public function hasRealm( string|Realm $realm ): bool
	{
		if ( $realm instanceof Realm ) {
			$realm = $realm->realmId;
		}

		return \array_reduce(
			$this->realmMap, static fn ( $r, $c ) => $c || $r->realmId === $realm, false );
	}

	/** @return array<Realm> */
	public function allRealms(): array
	{
		return \array_map( [$this->tenantConfig, 'getRealm'], \array_merge( ...\array_values( $this->realmMap ) ) );
	}

	public function getRealm( string $realm ): ?Realm
	{
		return $this->hasRealm( $realm ) ? $this->tenantConfig->getRealm( $realm ) : null;
	}

	public function getContact(): ?Contact
	{
		return null === $this->contactId ? null : $this->tenantConfig->getContact( $this->contactId );
	}

	public function getAuthenticatedUser( ?string $scope = null ): ?User
	{
		$user = $this->auth->getAuthenticatedUser( provider: $this, scope: $scope );

		return null === $user ? null : $this->verifyUserRealms( $user );
	}

	public function requireAuth( ?string $scope = null ): User
	{
		return $this->verifyUserRealms( $this->auth->requireAuth( provider: $this, scope: $scope ) );
	}

	/**
	 * @param array<string> $affiliations
	 *
	 * @return array<string,Realm>
	 */
	public function getRealmsByAffiliations( array $affiliations ): array
	{
		// TODO: Move this part to its own class, this is too tight a coupling
		$result = [];
		foreach ( $this->realmMap as $affiliation => $realms ) {
			if ( '*' === $affiliation || \in_array( $affiliation, $affiliations, true ) ) {
				if ( empty( $realms ) ) {
					// Stop evaluating more affiliations
					break;
				}
				foreach ( $realms as $realm ) {
					$result[$realm] = $this->tenantConfig->getRealm( $realm );
				}
			}
		}

		return $result;
	}

	private function verifyUserRealms( User $user ): User
	{
		foreach ( $user->getRealms() as $realmId => $realm ) {
			\assert( $realmId === $realm->realmId );
			if ( !$this->hasRealm( $realmId ) ) {
				throw new DomainException( 'Authenticated user has access to a realm that is invalid for this provider' );
			}
		}

		return $user;
	}
}
