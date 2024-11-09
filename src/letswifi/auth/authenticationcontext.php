<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\auth;

use DateTimeImmutable;
use DomainException;
use JsonSerializable;
use PDO;
use fyrkat\oauth\Client;
use fyrkat\oauth\OAuth;
use fyrkat\oauth\exception\BearerException;
use fyrkat\oauth\sealer\JWTSealer;
use fyrkat\oauth\sealer\PDOSealer;
use fyrkat\oauth\token\AccessToken;
use fyrkat\oauth\token\AuthorizationCode;
use fyrkat\oauth\token\Grant;
use fyrkat\oauth\token\RefreshToken;
use letswifi\auth\browser\BrowserAuthInterface;
use letswifi\provider\Provider;
use letswifi\provider\Realm;

class AuthenticationContext implements JsonSerializable
{
	public readonly string $kid;

	public readonly BrowserAuthInterface $browserAuth;

	protected ?OAuth $oauth = null;

	protected string $oauthSecret;

	public function __construct(
		public readonly string $authService,
		array $authServiceParams,
		array $oauth,
		protected readonly DateTimeImmutable $now = new DateTimeImmutable(),
	) {
		if ( !\preg_match( '/^[A-Z][A-Za-z0-9]+$/', $authService ) ) {
			throw new DomainException( 'Illegal auth.service specified in config' );
		}
		$authService = "letswifi\\auth\\browser\\{$authService}";
		$browserAuth = new $authService( ...$authServiceParams );
		\assert( $browserAuth instanceof BrowserAuthInterface );

		foreach ( $oauth as $kid => $o ) {
			// Use kid, issued and expiry fields in a better way
			if ( $now->getTimestamp() > $o['iss'] ) {
				$oauthSecret = \base64_decode( $o['key'], true );
				break;
			}
		}
		if ( !isset( $oauthSecret ) || !\is_string( $oauthSecret ) || !isset( $kid ) || !$browserAuth instanceof BrowserAuthInterface ) {
			throw new DomainException( 'No appropriate oauth key available' );
		}
		$this->oauthSecret = $oauthSecret;
		$this->kid = $kid;
		$this->browserAuth = $browserAuth;
	}

	public function registerOAuthPDO( PDO $pdo ): void
	{
		if ( null !== $this->oauth ) {
			throw new DomainException( 'OAuth is already registered' );
		}
		$accessTokenSealer = new JWTSealer( AccessToken::class, $this->oauthSecret );
		$authorizationCodeSealer = new JWTSealer( AuthorizationCode::class, $this->oauthSecret );
		$refreshTokenSealer = new PDOSealer( RefreshToken::class, $pdo );

		/** @psalm-suppress ArgumentTypeCoercion Psalm reverses parent/child (PSALMBUG) */
		$this->oauth = new OAuth( $accessTokenSealer, $authorizationCodeSealer, $refreshTokenSealer );
	}

	public function jsonSerialize(): array
	{
		return ['authService' => $this->authService, 'now' => $this->now];
	}

	public function getOAuthHandler(): OAuth
	{
		return $this->oauth ?? throw new DomainException( 'Cannot register client before OAuth has been registered' );
	}

	public function getUser( Provider $provider, ?string $scope = null, bool $force = false ): ?User
	{
		if ( null !== $scope ) {
			if ( null !== $this->oauth ) {
				try {
					$token = $this->oauth->getAccessTokenFromRequest( $scope );
					$grant = $token->getGrant();

					return $this->getUserFromGrant( $provider, $grant );
				} catch ( BearerException $e ) {
					return $force ? throw $e : null;
				}
			}
		} else {
			$userId = $force ? $this->browserAuth->getUserId() : $this->browserAuth->requireAuth();

			return null !== $userId ? $this->constructUser( $provider, $userId ) : null;
		}

		return null;
	}

	public function requireAuth( Provider $provider, ?string $scope = null ): User
	{
		if ( $user = $this->getUser( provider: $provider, scope: $scope, force: true ) ) {
			return $user;
		}

		throw new DomainException( 'Exhausted all available authentication methods without success' );
	}

	public function registerClient( Client $client ): void
	{
		$this->getOAuthHandler()->registerClient( $client );
	}

	private function getUserFromGrant( Provider $provider, Grant $grant ): User
	{
		$sub = $grant->getSub();
		if ( null === $grant->realm ) {
			throw new DomainException( "User {$sub} has no realm" );
		}

		$realm = $provider->getRealm( $grant->realm );
		if ( null === $realm ) {
			throw new DomainException( "Realm {$grant->realm} is not available at this provider" );
		}
		$affiliations = \explode( ',', $grant->__get( 'affiliations' ) ?? '' );

		return $this->constructUser(
			provider: $provider,
			userId: $sub,
			clientId: $grant->clientId,
			affiliations: $affiliations,
			realm: $realm,
		);
	}

	private function constructUser( Provider $provider, string $userId, ?string $clientId = null, ?array $affiliations = null, ?Realm $realm = null ): User
	{
		return new User(
			userId: $userId,
			realms: null === $realm
				? $provider->getRealmsByAffiliations( $this->browserAuth->getAffiliations() )
				: [$realm->realmId => $realm],
			affiliations: $affiliations ?? $this->browserAuth->getAffiliations(),
			clientId: $clientId,
			ip: $_SERVER['REMOTE_ADDR'] ?? null,
			userAgent: $_SERVER['HTTP_USER_AGENT'] ?? null,
		);
	}
}
