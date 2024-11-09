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
use letswifi\auth\AuthenticationContext;
use letswifi\auth\User;

class Provider implements JsonSerializable
{
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

	public static function fromArray( TenantConfig $tenantConfig, array $data ): self
	{
		$auth = null;
		$oauth = null;
		if ( \array_key_exists( 'auth', $data ) ) {
			$authService = $data['auth']['service'] ?? null;
			if ( null === $authService ) {
				throw new DomainException( 'No auth service is set for the provider' );
			}
			$authServiceParams = $data['auth']['param'] ?? [];
			$oauth = $data['auth']['oauth'];
			if ( null === $oauth ) {
				throw new DomainException( 'Provider oauth settings not specified' );
			}
			$auth = new AuthenticationContext( $authService, $authServiceParams, $oauth );
		}
		if ( null === $auth ) {
			throw new DomainException( 'Provider auth not specified' );
		}

		return new self(
			tenantConfig: $tenantConfig,
			host: $data['host'],
			displayName: $data['display_name'],
			auth: $auth,
			realmMap: $data['realm'],
			contactId: $data['contact'],
			description: $data['description'],
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

	public function getUser( ?string $scope = null ): ?User
	{
		return $this->auth->getUser( provider: $this, scope: $scope );
	}

	public function requireAuth( ?string $scope = null ): User
	{
		return $this->auth->requireAuth( provider: $this, scope: $scope );
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
}
