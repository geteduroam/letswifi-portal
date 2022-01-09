<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2021, Jørn Åne de Jong, Uninett AS <jornane.dejong@surf.nl>
 * Copyright: 2020-2021, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

require \implode(\DIRECTORY_SEPARATOR, [\dirname(__DIR__, 4), 'src', '_autoload.php']);

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();
$realm = $app->getRealm();

$app->requireAdmin( 'admin-ca-revoke' );

if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
	\header( 'Content-Type: text/plain', true, 405 );
	exit( "405 Method Not Allowed\r\n\r\nOnly POST is allowed for this resource\r\n" );
}

$realmManager = $app->getRealmManager();
if ( \array_key_exists( 'user', $_POST ) ) {
	$realmManager->revokeUser( $realm->getName(), $_POST['user'] );
}
if ( \array_key_exists( 'subject', $_POST ) ) {
	$realmManager->revokeSubject( $realm->getName(), $_POST['subject'] );
}

if ( $app->isBrowser() && \array_key_exists( 'returnTo', $_POST ) ) {
	\header( 'Location: ' . $_POST['returnTo'] );
} else {
	\header( 'X-Result: success', false, 204 ); // No Content
}