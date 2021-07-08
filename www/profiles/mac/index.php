<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2021, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * Copyright: 2020-2021, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

require \implode(\DIRECTORY_SEPARATOR, [\dirname(__DIR__, 3), 'src', '_autoload.php']);

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();
$realm = $app->getRealm();
$app->getUserFromBrowserSession( $realm );

// Create a short-lived session to allow the user ONE download without using post
// If the download would fail, the user is still presented with a download button
// on this page, which uses a more reliable POST.
// If the meta_redirect would go through too late (after session expiry),
// the page being redirected to will also contain an appropriate download button.
\session_start(['cookie_lifetime' => 60]);
$_SESSION['mobileconfig-download-token'] = true;

switch ( $_SERVER['REQUEST_METHOD'] ) {
	case 'GET': return $app->render(
		[
			'href' => '/profiles/mac/',
			'action' => '/profiles/new/',
			'device' => 'apple-mobileconfig',
			'meta_redirect' => '/profiles/new/?download&device=apple-mobileconfig',
		], 'mobileconfig-mac-new' );
}

\header( 'Content-Type: text/plain', true, 405 );
exit( "405 Method Not Allowed\r\n" );
