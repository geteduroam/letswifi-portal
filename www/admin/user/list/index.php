<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2022, Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: 2020-2022, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 4 ), 'src', '_autoload.php'] );
$basePath = '../../..';

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();

$app->requireAdmin( 'admin-user-list' );

$realmManager = $app->getRealmManager();
$realm = $app->getRealm();

$users = \array_unique( $realmManager->listUsers( $realm->getName() ) );

$app->render( [
	'href' => "{$basePath}/admin/user/list/",
	'jq' => '.users | map(.name)',
	'users' => \array_map( static function ( $user ) {
		return ['name' => $user, 'href' => '../get/?' . \http_build_query( ['user' => $user] )];
	}, $users ),
], 'admin-user-list', $basePath );
