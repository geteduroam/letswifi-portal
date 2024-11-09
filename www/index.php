<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

use letswifi\LetsWifiApp;

require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 1 ), 'src', '_autoload.php'] );
$basePath = '.';

$app = new LetsWifiApp( basePath: $basePath );
$app->registerExceptionHandler();

$vhost = \array_key_exists( 'HTTP_HOST', $_SERVER ) ? $_SERVER['HTTP_HOST'] : null;
$path = \strstr( $_SERVER['REQUEST_URI'] ?? '', '?', true ) ?: $_SERVER['REQUEST_URI'] ?? '';
$issuer = \is_string( $vhost ) ? "https://{$vhost}{$path}" : null;
$apiConfiguration = \is_string( $issuer ) ? [
	'authorization_endpoint' => "{$issuer}oauth/authorize/",
	'token_endpoint' => "{$issuer}oauth/token/",
	'eapconfig_endpoint' => "{$issuer}api/eap-config/",
	'mobileconfig_endpoint' => "{$issuer}api/eap-config/?format=mobileconfig",
	'profile_info_endpoint' => "{$issuer}profiles/info/",
] : null;

$app->render( [
	'href' => "{$basePath}/",
	'http://letswifi.app/api#v2' => $apiConfiguration,
], 'info', $basePath );
