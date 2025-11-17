<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

use letswifi\LetsWifiApp;
use letswifi\credential\Credential;

require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 3 ), 'src', '_autoload.php'] );
$app = new LetsWifiApp( basePath: '../..' );
$provider = $app->getProvider();
$user = $provider->requireAuth();
$credentialLog = $app->getCredentialLog( $user );
$credentialAdmin = $credentialLog->getCredentialAdministrator();
$admin = $user->promote();

$validOn = \array_key_exists( 'valid_on', $_GET ) && \is_string( $_GET['valid_on'] )
	? DateTimeImmutable::createFromFormat( '!Y-m-d\\TH:i:s', $_GET['valid_on'] ?? '' ) ?: null
	: null;

/** @psalm-suppress PossiblyUndefinedArrayOffset */
if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
	if ( \array_key_exists( 'revoke_credential', $_POST ) && \is_string( $_POST['revoke_credential'] ) ) {
		$credentialAdmin->revokeCredential(
			credentialId: $_POST['revoke_credential'],
		);
	}
	\header( 'Location: ?' . \http_build_query( $_GET ) );

	exit;
}
if ( \array_key_exists( 'realms', $_GET ) && \is_array( $_GET['realms'] ) ) {
	/** @psalm-suppress InvalidArgument */
	$realmsFilter = \array_diff( \array_keys( $admin->realms ), $_GET['realms'] )
	? \implode( ',', $_GET['realms'] )
	: null;
	\header( 'Location: ?' . \http_build_query( \array_filter( ['realms' => $realmsFilter] + $_GET ) ) );
	\setcookie(
		'filter_admin_realms', $realmsFilter ?? '',
		['path' => '/admin/', 'httponly' => true],
	);

	exit;
}

$realmsFilter = \array_filter( \explode( ',', $_GET['realms'] ?? $_COOKIE['filter_admin_realms'] ?? '' ) );
$requesterFilter = \array_key_exists( 'requester', $_GET ) && \is_string( $_GET['requester'] )
	? \trim( $_GET['requester'] ) ?: null
	: null;
$identFilter = \array_key_exists( 'ident', $_GET ) && \is_string( $_GET['ident'] )
	? \trim( $_GET['ident'] ) ?: null
	: null;
$revokedFilter = !\array_key_exists( 'revoked', $_GET ) || ( '0' !== $_GET['revoked'] && 'off' !== $_GET['revoked'] );

$app->render( [
	'_user' => $user,
	'_admin' => $admin,
	'_provider' => $provider,
	'_realms' => $admin->realms,

	'__filter_valid_on' => $validOn ?? new DateTimeImmutable(),
	'__filter_realms' => $realmsFilter,
	'__filter_requester' => $requesterFilter,
	'__filter_ident' => $identFilter,
	'__filter_revoked' => !$revokedFilter,

	'__admin_menu_prefix' => '../',
	'__admin_menu_active' => 'credentials/',
	'__admin_menu' => ( require '../_menu.php' ),

	'credentials' => isset( $ident )
		? \array_filter( [$credentialAdmin->getCredential( $ident )] )
		: \iterator_to_array( $credentialAdmin->listCredentials( $realmsFilter, requester: $requesterFilter, validOn: $validOn, unrevokedOnly: !$revokedFilter ) ),
], 'admin-credentials', [
	Credential::class => static fn ( Credential $c ) => [
		'not_before' => $c->getIssued()->format( 'Y-m-d' ),
		'not_after' => $c->getExpiry()?->format( 'Y-m-d' ),
		'revoked' => $c->getRevoked()?->format( 'Y-m-d' ),

		'requester_href' => '?' . \http_build_query( ['ident' => null, 'requester' => $c->userId] + $_GET ),
		'ident_href' => '?' . \http_build_query( ['ident' => $c->credentialId] + $_GET ),
		'realm_href' => '?' . \http_build_query( ['ident' => null, 'realms' => $c->realm->realmId] + $_GET ),
	],
] );
