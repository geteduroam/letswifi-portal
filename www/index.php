<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2022, Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: 2020-2022, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

require \implode(\DIRECTORY_SEPARATOR, [\dirname(__DIR__, 1), 'src', '_autoload.php']);
$basePath = '.';

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();

$vhost = \array_key_exists( 'HTTP_HOST', $_SERVER ) ? $_SERVER['HTTP_HOST'] : null;
$path = \strstr( $_SERVER['REQUEST_URI'] ?? '', '?', true ) ?: $_SERVER['REQUEST_URI'] ?? '';
$issuer = \is_string( $vhost ) ? "https://${vhost}${path}" : null;
$apiConfiguration = \is_string( $issuer ) ? [
	'authorization_endpoint' => "${issuer}oauth/authorize/",
	'token_endpoint' => "${issuer}oauth/token/",
	'eapconfig_endpoint' => "${issuer}api/eap-config/",
	'mobileconfig_endpoint' => "${issuer}api/eap-config/?format=mobileconfig",
] : null;

$app->render( [
	'href' => "${basePath}/",
	'http://letswifi.app/api#v1' => $apiConfiguration,
], 'info', $basePath );
