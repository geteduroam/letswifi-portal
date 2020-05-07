<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2020, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\browserauth;

class BasicAuth implements BrowserAuthInterface
{
	/** @var array<string,string> */
	private $params;

	public function __construct( array $params )
	{
		$this->params = $params;
	}

	public function requireAuth(): string
	{
		if ( \array_key_exists( 'PHP_AUTH_USER', $_SERVER ) ) {
			$user = $_SERVER['PHP_AUTH_USER'];
			if ( \array_key_exists( $user, $this->params ) ) {
				if ( \hash_equals( $this->params[$user], $_SERVER['PHP_AUTH_PW'] ) ) {
					return $user;
				}
			}
		}
		\header( 'WWW-Authenticate: Basic realm="letswifi-ca"', true, 401 );
		die( "401 Unauthorized\r\n" );
	}

	public function guessRealm( array $_ ): ?string
	{
		return null;
	}

	public function getLogoutURL( string $_ = null ): ?string
	{
		return null;
	}
}
