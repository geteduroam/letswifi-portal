<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

use letswifi\LetsWifiApp;
use letswifi\credential\RequesterAggregate;

require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 3 ), 'src', '_autoload.php'] );
$app = new LetsWifiApp( basePath: '../..' );
$provider = $app->getProvider();
$user = $provider->requireAuth();
$credentialLog = $app->getCredentialLog( $user );
$credentialAdmin = $credentialLog->getCredentialAdministrator();
$admin = $user->promote();

/** @psalm-suppress PossiblyUndefinedArrayOffset */
if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
	$validOn = \array_key_exists( 'valid_on', $_POST ) && \is_string( $_POST['valid_on'] )
		? DateTimeImmutable::createFromFormat( '!Y-m-d\\TH:i:s', $_POST['valid_on'] ?? '' ) ?: null
		: null;
	$realms = \array_key_exists( 'realms', $_POST ) && \is_string( $_POST['realms'] )
		? \array_filter( \explode( ',', $_POST['realms'] ?? '' ) )
		: [];
	if ( empty( $realms ) ) {
		throw new DomainException( 'No realms set' );
	}

	if ( \array_key_exists( 'revoke_requester', $_POST ) && \is_string( $_POST['revoke_requester'] ) ) {
		$credentialAdmin->revokeRequester( $_POST['revoke_requester'], $realms, $validOn );
	}
	\header( 'Location: ?' . \http_build_query( $_GET ) );

	exit;
}

if ( \array_key_exists( 'realms', $_GET ) && \is_array( $_GET['realms'] ) ) {
	/** @psalm-suppress InvalidArgument */
	$newRealmsFilter = \array_diff( \array_keys( $admin->realms ), $_GET['realms'] )
	? \implode( ',', $_GET['realms'] )
	: null;
	\header( 'Location: ?' . \http_build_query( \array_filter( ['realms' => $newRealmsFilter] + $_GET ) ) );
	\setcookie(
		'filter_admin_realms', $newRealmsFilter ?? '',
		['path' => '/admin/', 'httponly' => true],
	);

	exit;
}

$validOn = \array_key_exists( 'valid_on', $_GET ) && \is_string( $_GET['valid_on'] )
	? DateTimeImmutable::createFromFormat( '!Y-m-d\\TH:i:s', $_GET['valid_on'] ?? '' ) ?: null
	: null;

$realmsFilter = \array_filter( \explode( ',', $_GET['realms'] ?? $_COOKIE['filter_admin_realms'] ?? '' ) );
$requesterFilter = \array_key_exists( 'requester', $_GET ) && \is_string( $_GET['requester'] )
? \trim( $_GET['requester'] ) ?: null
:null;

$app->render( [
	'_user' => $user,
	'_admin' => $admin,
	'_provider' => $provider,
	'_realms' => $admin->realms,

	'__filter_valid_on' => $validOn ?? new DateTimeImmutable(),
	'__filter_realms' => $realmsFilter,
	'__filter_requester' => $requesterFilter,

	'__admin_menu_prefix' => '../',
	'__admin_menu_active' => 'requesters/',
	'__admin_menu' => ( require '../_menu.php' ),

	'requesters' => \iterator_to_array( $credentialAdmin->listRequesters( $realmsFilter, requester: $requesterFilter, validOn: $validOn ) ),
], 'admin-requesters', [
	RequesterAggregate::class => static fn ( RequesterAggregate $ra ) => [
		'requester_href' => '?' . \http_build_query( ['requester' => $ra->requester->name] + $_GET ),
		'realm_href' => '?' . \http_build_query( ['realms' => $ra->requester->realm] + $_GET ),
		'credential_href' => '../credentials/?' . \http_build_query( ['requester' => $ra->requester->name, 'realms' => $ra->requester->realm] ),
		'credential_unrevoked_href' => '../credentials/?' . \http_build_query( ['revoked' => 'off', 'requester' => $ra->requester->name, 'realms' => $ra->requester->realm] ),
		'earliest_valid' => $ra->earliestValid->format( 'Y-m-d' ),
		'last_valid' => $ra->lastValid->format( 'Y-m-d' ),
	],
] );
