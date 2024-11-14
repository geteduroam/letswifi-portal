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
\assert( \array_key_exists( 'REQUEST_METHOD', $_SERVER ) );

$app = new LetsWifiApp( basePath: $basePath );
$app->registerExceptionHandler();
$provider = $app->getProvider();
$user = $provider->getAuthenticatedUser( scope: 'eap-metadata' ) ?? $provider->requireAuth();

// Workaround for MacOS/ChromeOS flow; we want to provide the download through a meta refresh,
// but the refresh should only work once.
// Here we check a session, check if it can provide a download and then destroy the cookie
// so that the next request would point the user to the download page instead of the
// actual download.
//
// Normally, you'd use a POST to initiate the download, but POST cannot be initiated
// without user interaction, except with JavaScript which we don't use.
//
// CASE: user arrives here without $_GET[download],
// nothing special happens and we show the normal download page.
// CASE: user arrives with $_GET[download] but without cookie,
// we ignore the GET parameter and show the normal download page
// CASE: user arrives with $_GET[download] AND cookie to allow download,
// we provide the download as if a POST had been done;
// we're assuming the user came from /profiles/mac,
// so that's the page they're still looking at.
//
// NOTE: This protects against involuntary downloads ONLY,
// it DOES NOT, and is not intended to, protect against pressing F5 repeatedly
// It also does not protect against scripted downloads;
// the cookie is easily guessable, but why would a script do that if it can just POST.
if ( 'GET' === $_SERVER['REQUEST_METHOD'] && isset( $_GET['download'] ) ) {
	foreach ( ['apple-mobileconfig', 'google-onc', 'pkcs12'] as $formatCandidate ) {
		// Remove the cookie
		// Ensure this request can only be served one time
		// Does not affect the current $_COOKIE variable
		\setcookie( "{$formatCandidate}-download-token", '', [
			'expires' => 0,
			'httponly' => true,
			'secure' => false,
			'path' => '/',
			'samesite' => 'Strict',
		] );

		if ( $_GET['format'] === $formatCandidate && isset( $_COOKIE["{$formatCandidate}-download-token"] ) ) {
			$cookieTime = (int)$_COOKIE["{$formatCandidate}-download-token"];
			$earliestOkTime = \time() - 60; // allow cookies up to 60 seconds old
			if ( \time() >= $cookieTime && $cookieTime >= $earliestOkTime ) {
				$overrideMethod = 'POST';
				$overrideFormat = $formatCandidate;
			}
			if ( isset( $_COOKIE["{$formatCandidate}-download-passphrase"] ) ) {
				$overridePassphrase = $_COOKIE["{$formatCandidate}-download-passphrase"] ?: null;
			}
		}
	}

	// If we're not willing to fake a POST,
	// also remove the GET parameters that attempted this from the URL
	if ( !isset( $overrideMethod ) && \array_key_exists( 'REQUEST_URI', $_SERVER ) ) {
		\header( 'Location: ' . \strstr( $_SERVER['REQUEST_URI'], '?', true ) );

		exit;
	}
}

switch ( $overrideMethod ?? $_SERVER['REQUEST_METHOD'] ) {
	case 'GET': return $app->render(
		[
			'href' => "{$basePath}/profiles/new/",
			'formats' => [
				'apple-mobileconfig' => ['name' => 'Apple (iOS/MacOS)'],
				'eap-config' => ['name' => 'eap-config'],
				'google-onc' => ['name' => 'ChromeOS'],
				'pkcs12' => ['name' => 'PKCS12'],
			],
			'user' => $user,
			'realms' => $user->getRealms(),
			'app' => [
				'url' => "{$basePath}/app/",
			],
		], 'profile-advanced', $basePath, );

	case 'POST':
		if ( \array_key_exists( 'realm', $_POST ) ) {
			$realm = $_POST['realm'];
			$realm = $user->getRealm( \is_string( $realm ) ? $realm : null );
		} else {
			$realm = $user->getRealm();
		}
		$passphrase = $overridePassphrase ?? $_POST['passphrase'] ?? null ?: null;
		\assert( '' !== $passphrase );
		if ( \is_array( $passphrase ) ) {
			\header( 'Content-Type: text/plain', true, 400 );

			exit( "400 Bad Request\r\n\r\nInvalid passphrase\r\n" );
		}
		$credentialManager = $app->getUserCredentialManager( user: $user, realm: $realm );
		// TODO fix hardcoded credential type
		$credential = $credentialManager->issue( CertificateCredential::class );
		$format = $overrideFormat ?? null;
		foreach ( [$_POST, $_GET] as $candidate ) {
			if ( null === $format && \array_key_exists( 'format', $candidate ) && \is_string( $candidate['format'] ) ) {
				$format = $candidate['format'];
			}
		}
		$formatter = Format::getFormatter( $format ?? 'NULL',
			credential: $credential,
			profileSigner: $app->getProfileSigner(),
			passphrase: $passphrase,
		);
		$formatter->emit();

		exit; // should not be reached

	default:
		\header( 'Content-Type: text/plain', true, 405 );

		exit( "405 Method Not Allowed\r\n" );
}
