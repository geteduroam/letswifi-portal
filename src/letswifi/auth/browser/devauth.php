<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\auth\browser;

class DevAuth implements BrowserAuthInterface
{
	public function __construct( public readonly ?string $staticUser = null )
	{
		if ( \PHP_SAPI !== 'cli-server' ) {
			\header( 'Content-Type: text/plain' );

			exit( 'Development auth module cannot be used in production' . \PHP_EOL );
		}
	}

	public function requireAuth(): string
	{
		return $this->getUserId();
	}

	public function getUserId(): string
	{
		// Returns owner of this PHP file
		$user = $this->staticUser ?? \get_current_user();
		\assert( !\str_contains( $user, 'SYSTEM' ), 'File is owned by Windows SYSTEM user' );
		\assert( !\str_contains( $user, 'root' ), 'File is owned by unix root' );

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
		return ['staff', 'student', 'employee'];
	}
}
