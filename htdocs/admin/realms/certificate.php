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
use letswifi\error\ForbiddenException;
use letswifi\error\NotFoundException;

require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 3 ), 'src', '_autoload.php'] );
$app = new LetsWifiApp( basePath: '../..' );
$provider = $app->getProvider();
$user = $provider->requireAuth( scope: 'admin' );
$admin = $user->promote();
$credentialLog = $app->getCredentialLog( $user );
$credentialAdmin = $credentialLog->getCredentialAdministrator();

function getIndex( string $ca ): CADB
{
	global $credentialAdmin;
	$index = new CADB();
	foreach ( $credentialAdmin->listCredentialsBySigner( $ca ) as $credential ) {
		if ( $credential instanceof CertificateCredential && $credential->revoked ) {
			$index->revoked( $credential->subject, $credential->revoked, $credential->serial, $credential->expiry );
		}
	}

	return $index;
}

$realm = null;
$ca = $_GET['ca'] ?? null;
$fileType = $_GET['file'] ?? null;

if ( !\is_string( $fileType ) || !\is_string( $ca ) ) {
	throw new NotFoundException();
}

foreach ( $admin->realms as $realm ) {
	switch ( $fileType ) {
		case 'crt-der':
		case 'crt-pem':
			if ( \in_array( $ca, $realm->trust, true ) ) {
				break 2;
			}

			// crt can be exported if we trust the CA or if we use it for signing,
			// so fall through to the next test
		case 'index':
		case 'crl-der':
		case 'crl-pem':
			if ( $ca === $realm->signer ) {
				break 2;
			}

			// If we do not use this CA for signing, we don't have access.
			// Set the $realm variable to null to we can't use it down the line.
		default: $realm = null;
	}
}
if ( null === $realm ) {
	throw new ForbiddenException( 'Requested CA is not used by any of your realms' );
}
$safeFileName = \preg_replace( '/[^a-z0-9._-]/i', '', $ca ) ?: $fileType;
$encodedFileName = \rawurlencode( $ca );
$caCertificate = $app->profileService->getCertificate( $ca );

switch ( $fileType ) {
	case 'crt-der':
		\header( 'Content-Type: application/x-x509-ca-cert' );
		\header( "Content-Disposition: attachment; filename={$safeFileName}.crt; filename*=UTF-8''{$encodedFileName}.crt" );
		echo $caCertificate->getX509Der();

		exit;
	case 'crt-pem':
		\header( 'Content-Type: application/x-pem-file' );
		\header( "Content-Disposition: inline; filename={$safeFileName}.crt.pem; filename*=UTF-8''{$encodedFileName}.crt.pem" );
		echo $caCertificate->getX509Pem( withText: true );

		exit;
	case 'index':
		$index = \getIndex( $ca );
		\header( 'Content-Type: text/plain' );
		\header( "Content-Disposition: inline; filename={$safeFileName}.index.txt; filename*=UTF-8''{$encodedFileName}.index.txt" );
		echo $index->export();

		exit;
	case 'crl-der':
		// Not implemented yet
	case 'crl-pem':
		$index = \getIndex( $ca );
		$privateKey = $app->profileService->getPrivateKey( $ca );
		$crl = new CRL( $privateKey, $caCertificate );
		\header( 'Content-Type: application/x-pkcs7-crl' );
		\header( "Content-Disposition: inline; filename={$safeFileName}.crl.pem; filename*=UTF-8''{$encodedFileName}.crl.pem" );
		// TODO: Prints in PEM format, do we want to allow DER too?
		echo $crl->generateCrl( $index, validDays: 7 );

		exit;
}
