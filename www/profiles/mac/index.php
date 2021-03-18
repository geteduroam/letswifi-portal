<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2020, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * SPDX-License-Identifier: BSD-3-Clause
 */

require \implode(\DIRECTORY_SEPARATOR, [\dirname(__DIR__, 3), 'src', '_autoload.php']);

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();
$realm = $app->getRealm();
$browserAuth = $app->getBrowserAuthenticator( $realm );
$sub = $browserAuth->requireAuth();
$user = new letswifi\realm\User( $sub );

switch ( $_SERVER['REQUEST_METHOD'] ) {
	case 'GET': return $app->render(
		[
			'href' => '/profiles/mac/',
			'action' => '/profiles/new/',
			'device' => 'eap-config',
		], 'mobileconfig-mac-new' );
}

\header( 'Content-Type: text/plain', true, 405 );
exit( "405 Method Not Allowed\r\n" );
