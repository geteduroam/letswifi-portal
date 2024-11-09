<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

use letswifi\LetsWifiApp;
use letswifi\credential\CertificateCredential;
use letswifi\format\Format;

require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 3 ), 'src', '_autoload.php'] );
$basePath = '../..';
\assert( \array_key_exists( 'REQUEST_METHOD', $_SERVER ) ); // Psalm

if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
	\header( 'Content-Type: text/plain', true, 405 );

	exit( "405 Method Not Allowed\r\n\r\nOnly POST is allowed for this resource\r\n" );
}

$app = new LetsWifiApp( basePath: $basePath );
$app->registerExceptionHandler();
$provider = $app->getProvider();
$user = $provider->requireAuth( scope: 'eap-metadata' );
$realm = $user->getRealm();
$format = \array_key_exists( 'format', $_GET ) ? \is_string( $_GET['format'] ) ? $_GET['format'] : null : null;

$credentialManager = $app->getUserCredentialManager( user: $user, realm: $realm );
// TODO fix hardcoded credential type
$credential = $credentialManager->issue( CertificateCredential::class );
$formatter = Format::getFormatter( $format ?? 'eap-config',
	credential: $credential,
	profileSigner: $app->getProfileSigner(),
	passphrase: null,
);
$formatter->emit();

exit; // should not be reached
