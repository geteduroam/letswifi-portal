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
if ( 1 !== \count( $argv ) ) {
	echo $argv[0] . ' takes no arguments' . \PHP_EOL;
	exit( 2 );
}
require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 1 ), 'src', '_autoload.php'] );

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();
$realmManager = $app->getRealmManager();

$serverNames = $realmManager->getAllServerNames();
echo \implode( \PHP_EOL, $serverNames ) . \PHP_EOL;
