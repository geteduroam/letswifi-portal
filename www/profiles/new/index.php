<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2022, Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: 2020-2022, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

require \implode(\DIRECTORY_SEPARATOR, [\dirname(__DIR__, 3), 'src', '_autoload.php']);
$basePath = '../..';
\assert( \array_key_exists( 'REQUEST_METHOD', $_SERVER ) );

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();
$realm = $app->getRealm();
$user = $app->getUserFromBrowserSession( $realm );

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
// the cookie is easily guessable, but why would a script do that?
// It can just POST.
if ( 'GET' === $_SERVER['REQUEST_METHOD'] && isset( $_GET['download'] ) ) {
	foreach ( ['apple-mobileconfig', 'google-onc', 'pkcs12'] as $kind ) {
		// Ensure this request can only be served one time
		// Does not affect the current $_COOKIE variable
		\setcookie("${kind}-download-token", '', [
			'expires' => 0,
			'httponly' => true,
			'secure' => false,
			'path' => '/',
			'samesite' => 'Strict',
		]);

		if ( $_GET['device'] === $kind && isset( $_COOKIE["${kind}-download-token"] ) ) {
			$cookieTime = (int)$_COOKIE["${kind}-download-token"];
			$earliestOkTime = \time() - 60; // allow cookies up to 60 seconds old
			if ( \time() >= $cookieTime && $cookieTime >= $earliestOkTime ) {
				$overrideMethod = 'POST';
				$overrideDevice = $kind;
			}
			if ( isset( $_COOKIE["${kind}-download-passphrase"] ) ) {
				$overridePassphrase = $_COOKIE["${kind}-download-passphrase"] ?: null;
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
				'href' => "${basePath}/profiles/new/",
				'devices' => [
					'apple-mobileconfig' => [
						'name' => 'Apple (iOS/MacOS)',
					],
					'eap-config' => [
						'name' => 'eap-config',
					],
					'google-onc' => [
						'name' => 'ChromeOS',
					],
					'pkcs12' => [
						'name' => 'PKCS12',
					],
				],
				'app' => [
					'url' => "${basePath}/app/",
				],
			], 'profile-advanced', $basePath, );
	case 'POST':
		$passphrase = $overridePassphrase ?? $_POST['passphrase'] ?? null ?: null;
		\assert( '' !== $passphrase );
		if ( \is_array( $passphrase ) ) {
			\header( 'Content-Type: text/plain', true, 400 );
			exit( "400 Bad Request\r\n\r\nInvalid passphrase\r\n" );
		}
		switch ( $device = $overrideDevice ?? $_POST['device'] ?? '' ) {
			case 'apple-mobileconfig': $generator = $realm->getConfigGenerator( \letswifi\profile\generator\MobileConfigGenerator::class, $user, $passphrase ); break;
			case 'eap-config': $generator = $realm->getConfigGenerator( \letswifi\profile\generator\EapConfigGenerator::class, $user, $passphrase ); break;
			case 'pkcs12': $generator = $realm->getConfigGenerator( \letswifi\profile\generator\PKCS12Generator::class, $user, $passphrase ); break;
			case 'google-onc': $generator = $realm->getConfigGenerator( \letswifi\profile\generator\ONCGenerator::class, $user, $passphrase ); break;

			default:
				\header( 'Content-Type: text/plain', true, 400 );
				$deviceStr = \is_string( $device )
					? ": ${device}"
					: ''
					;
				exit( "400 Bad Request\r\n\r\nUnknown device${deviceStr}\r\n" );
		}
		$payload = $generator->generate();
		\header( 'Content-Disposition: attachment; filename="' . $generator->getFilename() . '"' );
		\header( 'Content-Type: ' . $generator->getContentType() );
		exit( $payload );
	default:
		\header( 'Content-Type: text/plain', true, 405 );
		exit( "405 Method Not Allowed\r\n" );
}
