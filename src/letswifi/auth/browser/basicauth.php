<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\auth\browser;

/** @psalm-immutable */
class BasicAuth implements BrowserAuthInterface
{
	public readonly array $server;

	/**
	 * @param array<string,string>        $accounts     username/password combinations
	 * @param array<string,array<string>> $affiliations Mapping username to affiliations
	 */
	public function __construct( private readonly array $accounts, public readonly array $affiliations = [], ?array $server = null )
	{
		$this->server = $server ?? $_SERVER;
	}

	/**
	 * @psalm-suppress ImpureFunctionCall The function will not return
	 */
	public function requireAuth(): string
	{
		$userId = $this->getUserId();
		if ( null !== $userId ) {
			return $userId;
		}
		\header( 'WWW-Authenticate: Basic realm="letswifi-ca"', true, 401 );

		exit( "401 Unauthorized\r\n" );
	}

	public function isLoggedIn(): bool
	{
		return null !== $this->getUserId();
	}

	public function getUserId(): ?string
	{
		if ( \array_key_exists( 'PHP_AUTH_USER', $this->server ) ) {
			$user = $this->server['PHP_AUTH_USER'];
			if ( \array_key_exists( $user, $this->accounts ) ) {
				if ( \hash_equals( $this->accounts[$user], $this->server['PHP_AUTH_PW'] ?? '' ) ) {
					return $user;
				}
			}
		}

		return null;
	}

	/**
	 * @param ?string $redirect @unused-param
	 */
	public function getLogoutURL( ?string $redirect = null ): ?string
	{
		return null;
	}

	public function getAffiliations(): array
	{
		if ( $userId = $this->getUserId() ) {
			return $this->affiliations[$userId] ?? [];
		}

		return [];
	}
}
