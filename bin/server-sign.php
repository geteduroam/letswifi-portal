#!/usr/bin/env php
<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2022, Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: 2020-2022, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

if ( \PHP_SAPI !== 'cli' ) {
	\header( 'Content-Type: text/plain', true, 403 );
	exit( "403 Forbidden\r\n\r\nThis script is intended to be run from the commandline only\r\n" );
}
if ( 3 !== \count( $argv ) && 4 !== \count( $argv ) ) {
	echo $argv[0] . " realm common_name [days_valid=1095]\n";
	exit( 2 );
}
require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 1 ), 'src', '_autoload.php'] );

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();
$realmManager = $app->getRealmManager();
$user = new letswifi\realm\User( 'cli', 'cli' );

$realm = $realmManager->getRealm( $argv[1] );
$pkcs12 = $realm->generateServerCertificate( $user, $argv[2], ( new DateTimeImmutable() )->add( new DateInterval( 'P' . ( 4 === \count( $argv ) ? $argv[3] : 1095 ) . 'D' ) ) );
echo $pkcs12->privateKey->getPrivateKeyPem( null );
echo $pkcs12->x509;
