<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

use letswifi\LetsWifiApp;

if ( !isset( $downloadFormat ) || !isset( $basePath ) ) {
	\header( 'Content-Type: text/plain', true, 400 );

	exit( "400 Bad Request\r\n\r\nInvalid request\r\n" );
}
\assert( \array_key_exists( 'REQUEST_METHOD', $_SERVER ) );

$href = "{$basePath}/profiles/new/{$downloadFormat}/";

$app = new LetsWifiApp( basePath: $basePath );
$app->registerExceptionHandler();
$provider = $app->getProvider();
$user = $provider->requireAuth();

// Create a short-lived cookie to allow the user ONE download without using POST
// If the download would fail, the user is still presented with a download button
// on this page, which uses a more reliable POST.
// If the meta_redirect would go through too late (after cookie expiry),
// the page being redirected to will also contain an appropriate download button.
\setcookie( "{$downloadFormat}-download-token", (string)\time(), [
	'expires' => 0, // session cookie
	'httponly' => true, // not available in JavaScript
	'secure' => false, // we don't care, this is not for security, and this helps with local devving
	'path' => '/', // make it available to /profiles/new as well; relative path's don't work here so use "/" for now
	'samesite' => 'Strict',
] );
if ( isset( $passphrase ) ) {
	\setcookie( "{$downloadFormat}-download-passphrase", $passphrase, [
		'expires' => 0, // session cookie
		'httponly' => true, // not available in JavaScript
		'secure' => false, // we don't care, this is not for security, and this helps with local devving
		'path' => '/', // make it available to /profiles/new as well; relative path's don't work here so use "/" for now
		'samesite' => 'Strict',
	] );
}

switch ( $_SERVER['REQUEST_METHOD'] ) {
	case 'GET': $app->render(
		[
			'passphrase' => ( $passphrase ?? null ) ?: null,
			'action' => "{$basePath}/profiles/new/",
			'format' => $downloadFormat,
			'user' => $user,
			'realms' => $user->getRealms(),
			'meta_redirect' => \count( $user->getRealms() ) === 1 ? "{$basePath}/profiles/new/?" . \http_build_query( ['download' => '1', 'format' => $downloadFormat] ) : null,
		], 'profile-download', $basePath, );
}

\header( 'Content-Type: text/plain', true, 405 );

exit( "405 Method Not Allowed\r\n" );
