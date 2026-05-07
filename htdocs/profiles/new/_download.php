<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

use letswifi\LetsWifiApp;
use letswifi\error\HttpMethodException;

if ( !isset( $downloadFormat ) || !isset( $urlRelativeBase ) ) {
	// Do not throw an exception here, because we haven't properly initialized;
	// the autoloader may not even be available
	// Just abort the old fashioned way.

	\header( 'Content-Type: text/plain', true, 400 );

	exit( "400 Bad Request\r\n\r\nInvalid request\r\n" );
}

$href = "{$urlRelativeBase}/profiles/new/{$downloadFormat}/";

$app = new LetsWifiApp( urlRelativeBase: $urlRelativeBase );
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

switch ( $_SERVER['REQUEST_METHOD'] ?? null ) {
	case 'GET': $app->render(
		[
			'passphrase' => ( $passphrase ?? null ) ?: null,
			'action' => "{$urlRelativeBase}/profiles/new/",
			'format' => $downloadFormat,
			'user' => $user,
			'realms' => $user->getRealms(),
			'meta_redirect' => \count( $user->getRealms() ) === 1 ? "{$urlRelativeBase}/profiles/new/?" . \http_build_query( ['download' => '1', 'format' => $downloadFormat] ) : null,
		], 'profile-download' );
}

throw new HttpMethodException( ['GET'] );
