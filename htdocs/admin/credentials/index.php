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
	\header( 'Location: ?' . \http_build_query( ['realms' => \implode( ',', $_GET['realms'] )] + $_GET ) );

	exit;
}

$realms = \array_key_exists( 'realms', $_GET )
	? \array_filter( \explode( ',', $_GET['realms'] ?? '' ) )
	: [];
$requester = \array_key_exists( 'requester', $_GET ) && \is_string( $_GET['requester'] )
? \trim( $_GET['requester'] ) ?: null
:null;
$app->render( [
	'user' => $user,
	'admin' => $admin,
	'provider' => $provider,

	'realms' => $admin->realms,
	'filter_valid_on' => $validOn ?? new DateTimeImmutable(),
	'filter_realms' => $realms,
	'filter_requester' => $requester,

	'admin_menu' => [
		'Credentials' => '../credentials/',
		'Requesters' => '../requesters/',
	],

	'credentials' => \iterator_to_array( $credentialAdmin->listCredentials( $realms, $validOn, requester: $requester ) ),
], 'admin-credentials', [
	Credential::class => static fn ( Credential $c ) => [
		'not_before' => $c->getIssued()->format( 'Y-m-d' ),
		'not_after' => $c->getExpiry()?->format( 'Y-m-d' ),
		'revoked' => $c->getRevoked()?->format( 'Y-m-d' ),

		'requester_href' => '?' . \http_build_query( ['requester' => $c->userId] + $_GET ),
		'realm_href' => '?' . \http_build_query( ['realms' => $c->realm->realmId] + $_GET ),
	],
] );
