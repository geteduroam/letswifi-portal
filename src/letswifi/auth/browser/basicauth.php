<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\auth\browser;

class BasicAuth implements BrowserAuthInterface
{
	public function __construct( public readonly array $accounts )
	{
	}

	public function requireAuth(): string
	{
		$userId = $this->getUserId();
		if ( null !== $userId ) {
			return $userId;
		}
		\header( 'WWW-Authenticate: Basic realm="letswifi-ca"', true, 401 );

		exit( "401 Unauthorized\r\n" );
	}

	public function getUserId(): ?string
	{
		if ( \array_key_exists( 'PHP_AUTH_USER', $_SERVER ) ) {
			$user = $_SERVER['PHP_AUTH_USER'];
			if ( \array_key_exists( $user, $this->accounts ) ) {
				if ( \hash_equals( $this->accounts[$user], $_SERVER['PHP_AUTH_PW'] ?? '' ) ) {
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
		return ['staff', 'student', 'employee'];
	}
}
