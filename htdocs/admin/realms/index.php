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

require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 3 ), 'autoload.php'] );
$app = new LetsWifiApp( urlRelativeBase: '../..' );
$provider = $app->getProvider();
$user = $provider->requireAuth( scope: 'admin' );
$admin = $user->promote();
$credentialLog = $app->getCredentialLog( $user );
$credentialAdmin = $credentialLog->getCredentialAdministrator();

if ( \array_key_exists( 'realm_id', $_GET ) && $realm = $admin->getRealm( $_GET['realm_id'] ) ) {
	$affiliationAllow = [];
	$affiliationBlock = [];
	foreach ( $provider->realmMap as $affiliation => $_ ) {
		$realms = $provider->getRealmsByAffiliations( [$affiliation] );
		if ( \in_array( $realm->realmId, \array_keys( $realms ), true ) ) {
			$affiliationAllow[] = $affiliation;
		} else {
			$affiliationBlock[] = $affiliation;
		}
	}
	$stats = \iterator_to_array( $credentialAdmin->getRealmStats( [$realm] ) );

	/** @psalm-suppress InvalidArgument https://github.com/vimeo/psalm/issues/11287 */
	$app->render( [
		'_user' => $user,
		'_admin' => $admin,
		'_provider' => $provider,

		'__admin_menu_prefix' => '../',
		'__admin_menu_active' => 'realms/',
		'__admin_menu' => ( require '../_menu.php' ),

		'signer_crl_href' => 'certificate.php?' . \http_build_query( ['realm_id' => $realm->realmId, 'ca' => $realm->signer, 'file' => 'crl-pem'] ),
		'signer_crt_href' => 'certificate.php?' . \http_build_query( ['realm_id' => $realm->realmId, 'ca' => $realm->signer, 'file' => 'crt-pem'] ),

		'realm' => $realm,
		'affiliations' => ['allow' => $affiliationAllow, 'block' => $affiliationBlock],
	], 'admin-realm', [
		Realm::class => static fn( Realm $r ): array => ( $stats[$r->realmId] ?? [] ) + [
			'trust_crt' => \array_map( static fn( string $trust ) => ['cn' => $trust, 'href' => 'certificate.php?' . \http_build_query( ['realm_id' => $r->realmId, 'ca' => $trust, 'file' => 'crt-pem'] )], $r->trust ),
		],
	] );
}
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
		'href' => '?' . \http_build_query( ['realm_id' => $r->realmId] ),
		'credential_href' => '../credentials/?' . \http_build_query( ['realms' => $r->realmId] ),
		'credential_unrevoked_href' => '../credentials/?' . \http_build_query( ['revoked' => 'off', 'realms' => $r->realmId] ),
		'requester_href' => '../requesters/?' . \http_build_query( ['realms' => $r->realmId] ),
	],
] );
