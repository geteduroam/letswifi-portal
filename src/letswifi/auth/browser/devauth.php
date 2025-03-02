<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\auth\browser;

class DevAuth implements BrowserAuthInterface
{
	/**
	 * @param array<string> $affiliations
	 */
	public function __construct( private readonly ?string $username = null, private readonly array $affiliations = [] )
	{
		if ( \PHP_SAPI !== 'cli-server' ) {
			\header( 'Content-Type: text/plain' );

			exit( 'Development auth module cannot be used in production' . \PHP_EOL );
		}
	}

	public function isLoggedIn(): bool
	{
		return true;
	}

	public function requireAuth(): string
	{
		return $this->getUserId();
	}

	public function getUserId(): string
	{
		// Get the running user ID
		$user = $this->username ?? \get_current_user();
		\assert( !\str_contains( $user, 'SYSTEM' ), 'Running as Windows SYSTEM user' );
		\assert( !\str_contains( $user, 'root' ), 'Running as root' );

		return $user;
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
		return $this->affiliations;
	}
}
