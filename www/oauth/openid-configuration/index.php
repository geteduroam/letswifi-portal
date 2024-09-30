<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2022, Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: 2020-2022, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

// Verbatim copy from jornane/php-oauth-server

$vhost = \array_key_exists( 'HTTP_HOST', $_SERVER ) ? $_SERVER['HTTP_HOST'] : null;
\assert( \is_string( $vhost ), 'HTTP_HOST should be string' );
$issuer = "https://{$vhost}";

$openIDConfiguration = [
	'issuer' => $issuer,
	'authorization_endpoint' => "{$issuer}/oauth/authorize/",
	'token_endpoint' => "{$issuer}/oauth/token/",
	'response_types_supported' => ['code'],
	'grant_types_supported' => [
		'authorization_code',
		'client_credentials', // TODO: only if enabled in config
	],
];

\header( 'Content-Type: application/json' );
exit( \json_encode( $openIDConfiguration, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR ) . \PHP_EOL );
