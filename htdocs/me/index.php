<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

use fyrkat\oauth\token\Grant;
use letswifi\LetsWifiApp;
use letswifi\credential\Credential;
use letswifi\tenant\Realm;

require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 2 ), 'src', '_autoload.php'] );
$basePath = '..';
$app = new LetsWifiApp( basePath: $basePath );
$app->registerExceptionHandler();
$provider = $app->getProvider();
$oauth = $provider->auth->oauth;
$user = $provider->requireAuth();
$indexUrl = $app->getIndexUrl();

$realmId = $_GET['realm'] ?? null;
$credentialLog = $app->getCredentialLog( $user );

if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
	if ( \array_key_exists( 'revoke_credential', $_POST ) && $revokeCredential = \is_string( $_POST['revoke_credential'] ) ? $_POST['revoke_credential'] : null ) {
		$credential = $credentialLog->getCredential( "CN={$revokeCredential}" );
		$credential->revoke();
	}
	if ( \array_key_exists( 'revoke_grant', $_POST ) && $revokeGrant = \is_string( $_POST['revoke_grant'] ) ? $_POST['revoke_grant'] : null ) {
		$oauth->revokeSession( $revokeGrant );
	}
	\header( "Location: {$indexUrl}" );

	exit; // Stop after redirect
}

/** @psalm-suppress InvalidArgument https://github.com/vimeo/psalm/issues/11287 */
$app->render(
	[
		'user' => $user,
		'realms' => $user->getRealms(),
		'credentials' => \iterator_to_array( $credentialLog->listCredentials() ),
		'grants' => \iterator_to_array( $oauth->listGrants( $user->userId ) ),
		'form_action' => $indexUrl,
		// TODO show realms with credential counts
	], 'me', $basePath, [
		Realm::class => static fn( Realm $r ) => [
			'logo' => $app->jsonOutputDelete,
			'logo_endpoint' => null === $r->logo
				? ( null === $provider->logo ? $app->jsonOutputDelete : "{$basePath}/profiles/info/logo.php" )
				: "{$basePath}/profiles/info/logo.php?" . \http_build_query( ['realm' => $r->realmId] ),
		],
		Credential::class => static fn( Credential $c ) => [
			'user' => $app->jsonOutputDelete,
			'provider' => $app->jsonOutputDelete,
			'realm' => $c->realm->realmId,
		],
		Grant::class => static fn( Grant $g ) => [
			'client_id' => $g->getClientId(),
			'not_before' => $g->getIat(),
			'not_after' => $g->getExp(),
			'session_id' => $g->sid,
			'realm' => $g->realm,
		],
	],
);
