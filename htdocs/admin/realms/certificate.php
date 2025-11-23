<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

use fyrkat\openssl\CADB;
use fyrkat\openssl\CRL;
use letswifi\LetsWifiApp;
use letswifi\credential\CertificateCredential;
use letswifi\error\NotFoundException;

require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 3 ), 'src', '_autoload.php'] );
$app = new LetsWifiApp( basePath: '../..' );
$provider = $app->getProvider();
$user = $provider->requireAuth();
$admin = $user->promote();
$credentialLog = $app->getCredentialLog( $user );
$credentialAdmin = $credentialLog->getCredentialAdministrator();
function getIndex( string $realmId ): CADB
{
	global $credentialAdmin;
	$index = new CADB();
	foreach ( $credentialAdmin->listCredentials( [$realmId], unrevokedOnly: false ) as $credential ) {
		if ( $credential instanceof CertificateCredential && $credential->revoked ) {
			// TODO: Report and fix; upstream should use interface
			\assert( $credential->revoked instanceof DateTimeImmutable );
			\assert( $credential->expiry instanceof DateTimeImmutable );

			$index->revoked( $credential->subject, $credential->revoked, $credential->serial, $credential->expiry );
		}
	}

	return $index;
}
if ( \array_key_exists( 'realm_id', $_GET ) && $realm = $admin->getRealm( $_GET['realm_id'] ) ) {
	$ca = $_GET['ca'] ?? null;
	$fileType = $_GET['file'] ?? null;
	if ( !\is_string( $ca ) || $ca !== $realm->signer && !\in_array( $ca, $realm->trust, true ) ) {
		throw new NotFoundException();
	}
	$caCertificate = $app->profileService->getCertificate( $ca );

	switch ( $fileType ) {
		case 'crt-der':
			\header( 'Content-Type: application/x-x509-ca-cert' );
			\header( "Content-Disposition: attachment; filename*=UTF-8''{$ca}.crt" );
			echo $caCertificate->getX509Der();

			exit;
		case 'crt-pem':
			\header( 'Content-Type: application/x-pem-file' );
			\header( "Content-Disposition: inline; filename*=UTF-8''{$ca}.crt.pem" );
			echo $caCertificate->getX509Pem( withText: true );

			exit;
		case 'index':
			$index = \getIndex( $realm->realmId );
			\header( 'Content-Type: text/plain' );
			\header( "Content-Disposition: inline; filename*=UTF-8''{$ca}.index.txt" );
			echo $index->export();

			exit;
		case 'crl-der':
			// Not implemented yet
		case 'crl-pem':
			$index = \getIndex( $realm->realmId );
			$privateKey = $app->profileService->getPrivateKey( $ca );
			$crl = new CRL( $privateKey, $caCertificate );
			\header( 'Content-Type: application/x-pkcs7-crl' );
			\header( "Content-Disposition: inline; filename*=UTF-8''{$ca}.crl.pem" );
			// TODO: Prints in PEM format, do we want to allow DER too?
			echo $crl->generateCrl( $index, validDays: 7 );

			exit;
	}
}

throw new NotFoundException();
