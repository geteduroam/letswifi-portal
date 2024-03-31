<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2022, Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: 2020-2022, Paul Dekkers, SURF <paul.dekkers@surf.nl>
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
				if ( \hash_equals( $this->params[$user], $_SERVER['PHP_AUTH_PW'] ?? '' ) ) {
					return $user;
				}
			}
		}
		\header( 'WWW-Authenticate: Basic realm="letswifi-ca"', true, 401 );
		exit( "401 Unauthorized\r\n" );
	}

	/**
	 * @param ?string $redirect @unused-param
	 */
	public function getLogoutURL( string $redirect = null ): ?string
	{
		return null;
	}

	public function getRealm(): ?string
	{
		return null;
	}
}
