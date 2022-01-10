<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2021, Jørn Åne de Jong, Uninett AS <jornane.dejong@surf.nl>
 * Copyright: 2020-2021, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

require \implode(\DIRECTORY_SEPARATOR, [\dirname(__DIR__, 4), 'src', '_autoload.php']);

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();

$app->requireAdmin( 'admin-user-list' );

$realmManager = $app->getRealmManager();
$realm = $app->getRealm();

$users = \array_unique( $realmManager->listUsers( $realm->getName() ) );

$app->render( [
	'href' => '/admin/user/list/',
	'jq' => '.users | map(.name)',
	'users' => \array_map( static function ( $user ) {
		return ['name' => $user, 'href' => '../get/?' . \http_build_query( ['user' => $user] )];
	}, $users ),
], 'admin-user-list' );
