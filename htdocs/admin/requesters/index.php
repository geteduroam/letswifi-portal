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

	if ( \array_key_exists( 'revoke_requester', $_POST ) ) {
		$value = \explode( '@', \is_string( $_POST['revoke_requester'] ) ? $_POST['revoke_requester'] : '' );
		if ( \count( $value ) !== 2 || null === $validOn ) {
			throw new DomainException( 'Incorrect format for requester to revoke' );
		}
		$requester = \base64_decode( $value[0], true );
		$realm = \base64_decode( $value[1], true );
		$credentialAdmin->revokeRequester(
			requester: $requester,
			realm: $realm,
			validOn: $validOn,
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

$validOn = \array_key_exists( 'valid_on', $_GET ) && \is_string( $_GET['valid_on'] )
	? DateTimeImmutable::createFromFormat( '!Y-m-d\\TH:i:s', $_GET['valid_on'] ?? '' ) ?: null
	: null;

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
		'Realms' => '../realms/',
		'Requesters' => '../requesters/',
		'Revocations' => '../revocations/',
	],

	'requesters' => \iterator_to_array( $credentialAdmin->listRequesters( $realms, $validOn, requester: $requester ) ),
], 'admin-requesters', [
	RequesterAggregate::class => static fn ( RequesterAggregate $ra ) => [
		'requester_href' => '?' . \http_build_query( ['id' => "{$ra->requester->name}@{$ra->requester->realm}"] + $_GET ),
		'realm_href' => '?' . \http_build_query( ['realms' => $ra->requester->realm] + $_GET ),
		'revoke_id' => \rtrim( \base64_encode( $ra->requester->name ), '=' ) . '@' . \rtrim( \base64_encode( $ra->requester->realm ), '=' ),
		'earliest_valid' => $ra->earliestValid->format( 'Y-m-d' ),
		'last_valid' => $ra->lastValid->format( 'Y-m-d' ),
	],
] );
