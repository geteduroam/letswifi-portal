<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\tenant;

use DomainException;
use JsonSerializable;
use fyrkat\multilang\MultiLanguageString;
use letswifi\auth\AuthenticationContext;
use letswifi\auth\User;
use letswifi\configuration\Dictionary;

class Provider implements JsonSerializable
{
	/**
	 * @param array<string,array<string>> $realmMap affiliation => realms
	 */
	public function __construct(
		private readonly TenantConfig $tenantConfig,
		public readonly string $host,
		public readonly MultiLanguageString $displayName,
		public readonly AuthenticationContext $auth,
		public readonly array $realmMap,
		public readonly ?string $contactId = null,
		public readonly ?MultiLanguageString $description = null,
		public readonly ?string $profileSigner = null,
	) {
	}

	/**
	 * @return array{host:string,display_name:MultiLanguageString,realm_map:array<string,array<string>>,contact:?Contact,description:?MultiLanguageString}
	 */
	public function jsonSerialize(): array
	{
		return [
			'host' => $this->host,
			'display_name' => $this->displayName,
			'realm_map' => $this->realmMap,
			'contact' => $this->getContact(),
			'description' => $this->description,
		];
	}

	public static function fromConfig( TenantConfig $tenantConfig, Dictionary $data ): self
	{
		$authData = $data->getDictionary( 'auth' );
		$authService = $authData->getString( 'service' );
		$authServiceParams = $authData->getRawArray( 'param' );
		$auth = new AuthenticationContext(
			authService: $authService,
			authServiceParams: $authServiceParams,
			oauthSecret: $data->getString( 'oauthsecret' ),
			oauthClients: $data->getRawArray( 'clients' ),
			pdoData: $data->getDictionary( 'pdo' ),
		);

		return new self(
			tenantConfig: $tenantConfig,
			host: $data->getParentKey(),
			displayName: $data->getMultiLanguageString( 'display_name' ),
			auth: $auth,
			realmMap: $data->getRawArray( 'realm' ),
			contactId: $data->getString( 'contact' ),
			description: $data->getMultiLanguageStringOrNull( 'description' ),
			profileSigner: $data->getStringOrNull( 'profile-signer' ),
		);
	}

	public function hasRealm( string|Realm $realm ): bool
	{
		if ( $realm instanceof Realm ) {
			$realm = $realm->realmId;
		}

		foreach ( $this->realmMap as $realms ) {
			if ( \in_array( $realm, $realms, true ) ) {
				return true;
			}
		}

		return false;
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
		// It might be better in the Realm class, or in the \letswifi\auth namespace.
		$result = [];
		foreach ( $this->realmMap as $affiliation => $realms ) {
			if ( '' === $affiliation || \in_array( $affiliation, $affiliations, true ) ) {
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
