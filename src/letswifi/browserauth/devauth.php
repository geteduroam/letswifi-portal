<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\browserauth;

class DevAuth implements BrowserAuthInterface
{
	/** @var array<string,string> */
	private $params;

	public function __construct( array $params )
	{
		if ( \PHP_SAPI !== 'cli-server' ) {
			\header( 'Content-Type: text/plain' );

			exit( 'Development auth module cannot be used in production' . \PHP_EOL );
		}
		$this->params = $params;
	}

	public function requireAuth(): string
	{
		// Returns owner of this PHP file
		$user = \get_current_user();
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

	public function getRealm(): ?string
	{
		return null;
	}
}
