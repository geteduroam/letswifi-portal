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
	// TODO make validity configurable
	echo 'cat key.pem cert.pem ca.pem | ' . $argv[0] . "\n";
	exit( 2 );
}
require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 1 ), 'src', '_autoload.php'] );

use fyrkat\openssl\PrivateKey;
use fyrkat\openssl\X509;

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();
$realmManager = $app->getRealmManager();

$stdin = \file_get_contents( 'php://stdin' );
\preg_match_all( '/(^|\n)-----BEGIN( EC)? PRIVATE KEY-----\n.*?\n-----END\1 PRIVATE KEY-----($|\n)/sm', $stdin, $keys );
\preg_match_all( '/(^|\n)-----BEGIN CERTIFICATE-----\n.*?\n-----END CERTIFICATE-----($|\n)/sm', $stdin, $certificates );

$keys = \array_map( static function ( string $key ) {
	return new PrivateKey( $key );
}, $keys[0] );
$certificates = \array_map( static function ( string $certificate ) {
	return new X509( $certificate );
}, $certificates[0] );

for ( $i = \count( $certificates ) - 1; 0 <= $i; --$i ) {
	$x509 = $certificates[$i];
	$sub = (string)$x509->getSubject();
	if ( null !== $realmManager->getCA( $sub ) ) {
		echo "Skipping {$sub} (already imported)\n";
		continue;
	}
	$key = null;
	foreach ( $keys as $candidateKey ) {
		if ( $x509->checkPrivateKey( $candidateKey ) ) {
			$key = $candidateKey;
			break;
		}
	}
	echo 'Importing';
	if ( null !== $key ) {
		echo ' with key';
	}
	echo ":\n";
	echo 'i: ' . $x509->getIssuerSubject() . "\n";
	echo 's: ' . $sub . "\n";
	try {
		$realmManager->importCA( $x509, $key );
	} catch ( Exception $e ) {
		echo 'ERR: ' . $e->getMessage() . "\n";
	}
}
