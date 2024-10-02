#!/usr/bin/env php
<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

if ( \PHP_SAPI !== 'cli' ) {
	\header( 'Content-Type: text/plain', true, 403 );

	exit( "403 Forbidden\r\n\r\nThis script is intended to be run from the commandline only\r\n" );
}
if ( \count( $argv ) < 2 ) {
	echo $argv[0] . ' servername...' . \PHP_EOL;
	echo 'hint: ' . \dirname( $argv[0] ) . '/list-servernames.php | xargs ' . $argv[0] . \PHP_EOL;

	exit( 2 );
}

require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 1 ), 'src', '_autoload.php'] );

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();
$realmManager = $app->getRealmManager();

$certificates = \array_unique( \array_map(
	static fn ( $r ) => $r->getSigningCACertificate(),
	$realmManager->getRealmsByServerName( \array_slice( $argv, 1 ) ),
) );
echo \implode( '', $certificates );
