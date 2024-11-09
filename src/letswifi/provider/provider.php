<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\provider;

use DateTimeImmutable;
use DomainException;
use JsonSerializable;
use PDO;
use fyrkat\oauth\OAuth;
use fyrkat\oauth\sealer\JWTSealer;
use fyrkat\oauth\sealer\PDOSealer;
use fyrkat\oauth\token\AccessToken;
use fyrkat\oauth\token\AuthorizationCode;
use fyrkat\oauth\token\RefreshToken;
use letswifi\auth\browser\BrowserAuthInterface;

class Provider implements JsonSerializable
{
	public function __construct(
		private readonly TenantConfig $tenantConfig,
		public readonly string $host,
		public readonly string $displayName,
		public readonly BrowserAuthInterface $auth,
		public readonly array $realmMap,
		private readonly array $oauth,
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
			$authParam = $data['auth']['param'] ?? [];
			if ( \is_string( $authService ) && \is_array( $authParam ) ) {
				if ( !\preg_match( '/^[A-Z][A-Za-z0-9]+$/', $authService ) ) {
					throw new DomainException( 'Illegal auth.service specified in config' );
				}
				$authService = "letswifi\\browserauth\\{$authService}";
				$auth = new $authService( ...$authParam );
				\assert( $auth instanceof BrowserAuthInterface );
			}
			$oauth = $data['auth']['oauth'];
		}
		if ( null === $auth || null === $oauth ) {
			throw new DomainException( 'Provider auth not specified' );
		}

		return new self(
			tenantConfig: $tenantConfig,
			host: $data['host'],
			displayName: $data['display_name'],
			auth: $auth,
			realmMap: $data['realm'],
			oauth: $oauth,
			contactId: $data['contact'],
			description: $data['description'],
		);
	}

	/**
	 * @psalm-suppress ArgumentTypeCoercion Psalm incorrectly things JWTSealer is parent of Sealer, it's the other way around (PSALMBUG)
	 */
	public function getOAuthHandler( PDO $pdo, DateTimeImmutable $now ): OAuth
	{
		// TODO: Move this part to its own class, this is too tight a coupling
		foreach ( $this->oauth as $oauth ) {
			// Use kid, issued and expiry fields in a better way
			if ( $now->getTimestamp() > $oauth['iss'] ) {
				$secret = \base64_decode( $oauth['key'], true );
				break;
			}
		}
		if ( !isset( $secret ) || !\is_string( $secret ) ) {
			throw new DomainException( 'No appropriate oauth key available' );
		}
		$accessTokenSealer = new JWTSealer( AccessToken::class, $secret );
		$authorizationCodeSealer = new JWTSealer( AuthorizationCode::class, $secret );
		$refreshTokenSealer = new PDOSealer( RefreshToken::class, $pdo );

		return new OAuth( $accessTokenSealer, $authorizationCodeSealer, $refreshTokenSealer );
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

	public function getUser(): ?User
	{
		$userId = $this->auth->getUserId();

		return null === $userId ? null : $this->constructUser( $userId );
	}

	public function requireAuth(): User
	{
		return $this->constructUser( $this->auth->requireAuth() );
	}

	private function constructUser( string $userId ): User
	{
		return new User(
			tenantConfig: $this->tenantConfig,
			provider: $this,
			userId: $userId,
			affiliations: $this->auth->getAffiliations(),
			clientId: null,
			ip: $_SERVER['REMOTE_ADDR'] ?? null,
			userAgent: $_SERVER['HTTP_USER_AGENT'] ?? null,
		);
	}
}
