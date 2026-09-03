<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\profile;

use DateInterval;
use DomainException;
use JsonSerializable;
use fyrkat\configmap\Dictionary;
use fyrkat\multilang\MultiLanguageString;
use letswifi\auth\AuthenticationContext;
use letswifi\auth\User;

class Provider implements JsonSerializable
{
	/**
	 * @param array<string,array<string>> $realmAccess affiliation => realms
	 * @param array<Location>             $location
	 * @param array<string>               $admins      Identities that are considered to be admins
	 */
	public function __construct(
		private readonly ProfileService $tenantConfig,
		public readonly string $host,
		public readonly MultiLanguageString $displayName,
		public readonly AuthenticationContext $auth,
		public readonly array $realmAccess,
		public readonly array $location = [],
		public readonly ?Logo $logo = null,
		public readonly ?string $contactId = null,
		public readonly ?MultiLanguageString $description = null,
		public readonly ?string $profileSigner = null,
		public readonly array $admins = [],
	) {
		$realmAccess || throw new DomainException( "Provider {$host}: realm map cannot be empty" );
	}

	/**
	 * @return array{host:string,display_name:MultiLanguageString,realm_access:array<string,array<string>>,contact:?Contact,description:?MultiLanguageString,location:array<Location>,logo:bool}
	 */
	public function jsonSerialize(): array
	{
		return [
			'host' => $this->host,
			'display_name' => $this->displayName,
			'realm_access' => $this->realmAccess,
			'contact' => $this->getContact(),
			'description' => $this->description,
			'location' => $this->location,
			'logo' => isset( $this->logo ),
		];
	}

	public static function fromConfig( ProfileService $tenantConfig, Dictionary $providerData ): self
	{
		$authData = $providerData->getDictionaryOrNull( 'auth' );
		$authService = $authData?->getString( 'service' ) ?? $providerData->getString( 'auth_service' );
		$authServiceParams = $authData?->getDictionary( 'param' ) ?? $providerData->getDictionary( 'auth_param' );
		$longLivedGrantTokenValidity = new DateInterval( 'P6M' );
		if ( $authData->has( 'longLivedGrantTokenValidity' ) ) {
			$longLivedGrantTokenValidity = static::getTokenValidity( $authData->getInt( 'longLivedGrantTokenValidity' ) );
		}
		$auth = new AuthenticationContext(
			authService: $authService,
			authServiceParams: $authServiceParams,
			oauthSecret: $providerData->getString( 'oauthsecret' ),
			oauthClients: $providerData->getDictionary( 'clients' ),
			pdoData: $providerData->getDictionary( 'pdo' ),
			longLivedGrantTokenValidity: $longLivedGrantTokenValidity,
		);

		$location = $providerData->getDictionaryList( 'location' );
		$logo = $providerData->getDictionaryOrNull( 'logo' );

		return new self(
			tenantConfig: $tenantConfig,
			host: $providerData->getParentKey(),
			displayName: $providerData->getObject( 'display_name', MultiLanguageString::class ),
			auth: $auth,
			// Rename "realm" to "realm_access"
			realmAccess: $providerData->getArrayOrNull( 'realm_access' ) ?? $providerData->getArrayOrNull( 'realm' ) ?? $providerData->getArray( 'realm_access' ),
			location: \array_map( [Location::class, 'fromConfig'], $location ),
			logo: null === $logo ? null : Logo::fromConfig( $logo ),
			contactId: $providerData->getStringOrNull( 'contact' ),
			description: $providerData->getObject( 'description', MultiLanguageString::class ),
			// Rename "profile-signer" to "profile_signer"
			profileSigner: $providerData->getStringOrNull( 'profile_signer' ) ?? $providerData->getStringOrNull( 'profile-signer' ),
			admins: $providerData->has( 'admins' ) ? $providerData->getStrings( 'admins' ) : [],
		);
	}

	public function hasRealm( string|Realm $realm ): bool
	{
		if ( $realm instanceof Realm ) {
			$realm = $realm->realmId;
		}

		foreach ( $this->realmAccess as $realms ) {
			if ( \in_array( $realm, $realms, true ) ) {
				return true;
			}
		}

		return false;
	}

	/** @return array<string,Realm> */
	public function allRealms(): array
	{
		$keys = \array_merge( ...\array_values( $this->realmAccess ) );
		$values = \array_map( [$this->tenantConfig, 'getRealm'], $keys );

		return \array_combine( $keys, $values );
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
		return $this->auth->getAuthenticatedUser( provider: $this, scope: $scope );
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
		$result = [];
		foreach ( $this->realmAccess as $affiliation => $realms ) {
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

	protected static function getTokenValidity( int|string|DateInterval $in ): DateInterval
	{
		if ( $in instanceof DateInterval ) {
			return $in;
		}
		if ( \is_int( $in ) && 0 < $in ) {
			return new DateInterval( "P{$in}D" );
		}
		if ( \is_string( $in ) ) {
			if ( $result = DateInterval::createFromDateString( $in ) ) {
				return $result;
			}
		}

		throw new DomainException( 'Invalid validity ' . $in );
	}
}
