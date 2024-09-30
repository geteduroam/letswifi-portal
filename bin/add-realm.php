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
	// TODO make validity configurable
	echo $argv[0] . " realm client_cert_validity_days [common_name]\n";
	exit( 2 );
}
require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 1 ), 'src', '_autoload.php'] );

use fyrkat\openssl\CSR;
use fyrkat\openssl\DN;
use fyrkat\openssl\OpenSSLConfig;
use fyrkat\openssl\OpenSSLKey;
use fyrkat\openssl\PrivateKey;

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();
$realmManager = $app->getRealmManager();

$caPrivKey = new PrivateKey( new OpenSSLConfig( privateKeyType: OpenSSLKey::KEYTYPE_EC ) );
$caCsr = CSR::generate(
	new DN( ['CN' => $argv[3] ?? ( $argv[1] . ' Let\'s Wi-Fi CA' )] ), // Subject
	$caPrivKey, // CA key
);
$caCertificate = $caCsr->sign(
	null, // CA certificate
	$caPrivKey, // CA key
	18250, // Validity in days
	new OpenSSLConfig( x509Extensions: OpenSSLConfig::X509_EXTENSION_CA ), // EKU
);

$realmManager->createRealm( $argv[1] );
$realmManager->importCA( $caCertificate, $caPrivKey );
$realmManager->addTrustedCa( $argv[1], $caCertificate->getSubject()->__toString() );
$realmManager->setSignerCa( $argv[1], $caCertificate->getSubject()->__toString(), new DateInterval( 'P' . $argv[2] . 'D' ) );
$realmManager->addServer( $argv[1], 'radius.' . $argv[1] );
