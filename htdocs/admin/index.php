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
$user = $provider->getAuthenticatedUser( scope: 'admin' ) ?? $provider->requireAuth();
$admin = $user->promote();

$app->render( [
	'_user' => $user,
	'_admin' => $admin,
	'_provider' => $provider,

	'__admin_menu_prefix' => '',
	'__admin_menu_active' => '',
	'__admin_menu' => ( require '_menu.php' ),
], 'admin' );
