<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2022, Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: 2020-2022, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

require \implode(\DIRECTORY_SEPARATOR, [\dirname(__DIR__, 3), 'src', '_autoload.php']);
$basePath = '../..';
\assert( \array_key_exists( 'REQUEST_METHOD', $_SERVER ) );

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();
$realm = $app->getRealm();
// just trigger a login
$app->getUserFromBrowserSession( $realm );

// Create a short-lived cookie to allow the user ONE download without using POST
// If the download would fail, the user is still presented with a download button
// on this page, which uses a more reliable POST.
// If the meta_redirect would go through too late (after cookie expiry),
// the page being redirected to will also contain an appropriate download button.
\setcookie('mobileconfig-download-token', (string)\time(), [
	'expires' => 0, // session cookie
	'httponly' => true, // not available in JavaScript
	'secure' => false, // we don't care, this is not for security, and this helps with local devving
	'path' => '/', // make it available to /profiles/new as well; relative path's don't work here so use "/" for now
	'samesite' => 'Strict',
]);

switch ( $_SERVER['REQUEST_METHOD'] ) {
	case 'GET': return $app->render(
			[
				'href' => "${basePath}/profiles/mac/",
				'action' => "${basePath}/profiles/new/",
				'device' => 'apple-mobileconfig',
				'meta_redirect' => "${basePath}/profiles/new/?" . \http_build_query( ['download' => '1', 'device' => 'apple-mobileconfig'] ),
			], 'mobileconfig-mac-new', $basePath, );
}

\header( 'Content-Type: text/plain', true, 405 );
exit( "405 Method Not Allowed\r\n" );
