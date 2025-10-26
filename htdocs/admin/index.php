<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

use letswifi\LetsWifiApp;

require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 2 ), 'src', '_autoload.php'] );
$app = new LetsWifiApp( basePath: '..' );
$provider = $app->getProvider();
$oauth = $provider->auth->oauth;
$user = $provider->requireAuth();
$admin = $user->promote();

$app->render( [
	'user' => $user,
	'admin' => $admin,
	'provider' => $provider,

	'admin_menu' => [
		'Requesters' => 'requesters/',
	],
], 'admin' );
