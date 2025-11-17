<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

use letswifi\LetsWifiApp;
use letswifi\profile\Realm;

require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 3 ), 'src', '_autoload.php'] );
$app = new LetsWifiApp( basePath: '../..' );
$provider = $app->getProvider();
$user = $provider->requireAuth();
$admin = $user->promote();
$credentialLog = $app->getCredentialLog( $user );
$credentialAdmin = $credentialLog->getCredentialAdministrator();

$stats = \iterator_to_array( $credentialAdmin->getRealmStats() );

/** @psalm-suppress InvalidArgument https://github.com/vimeo/psalm/issues/11287 */
$app->render( [
	'_user' => $user,
	'_admin' => $admin,
	'_provider' => $provider,

	'__admin_menu_prefix' => '../',
	'__admin_menu_active' => 'realms/',
	'__admin_menu' => ( require '../_menu.php' ),

	'realms' => $admin->getRealms(),
], 'admin-realms', [
	Realm::class => static fn( Realm $r ): array => ( $stats[$r->realmId] ?? [] ) + [
		'href' => '?' . \http_build_query( ['realm' => $r->realmId] ),
		'credential_href' => 'credentials/?' . \http_build_query( ['realms' => $r->realmId] ),
		'credential_unrevoked_href' => 'credentials/?' . \http_build_query( ['revoked' => 'off', 'realms' => $r->realmId] ),
		'requester_href' => 'requesters/?' . \http_build_query( ['realms' => $r->realmId] ),
	],
] );
