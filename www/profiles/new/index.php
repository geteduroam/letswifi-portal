<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2021, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * Copyright: 2020-2021, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

require \implode(\DIRECTORY_SEPARATOR, [\dirname(__DIR__, 3), 'src', '_autoload.php']);

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();
$realm = $app->getRealm();
$user = $app->getUserFromBrowserSession( $realm );

// Workaround for MacOS flow; we want to provide the download through a meta refresh,
// but the refresh should only work once.
// Here we load a session, check if it can provide a download and then destroy the session
// so that the next request would point the user to the download page instead of the
// actual download.
// Normally, you'd use a POST to initiate the download, but POST cannot be initiated
// without user interaction, except with JavaScript which we don't use.
//
// CASE: user arrives here without $_GET[download],
// nothing special happens and we show the normal download page.
// CASE: user arrives with $_GET[download] but without session,
// we ignore the GET parameter and show the normal download page
// CASE: user arrives with $_GET[download] AND session to allow download,
// we provide the download as if a POST had been done;
// assuming user came from /profiles/mac,
// so that's the page that stays in the browser.
if ( 'GET' === $_SERVER['REQUEST_METHOD'] && isset( $_GET['download'] ) ) {
	\session_start(['cookie_lifetime' => 1]);
	if ( $_SESSION['mobileconfig-download-token'] ) {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST['device'] = $_GET['device'];
	}
	\session_destroy();
}

switch ( $_SERVER['REQUEST_METHOD'] ) {
	case 'GET': return $app->render(
		[
			'href' => '/profiles/new/',
			'devices' => [
				'apple-mobileconfig' => [
					'name' => 'Apple (iOS/MacOS)',
				],
				'eap-config' => [
					'name' => 'eap-config',
				],
				'pkcs12' => [
					'name' => 'PKCS12',
				],
			],
			'app' => [
				'url' => '../../app/',
			],
		], 'profiles-new' );
	case 'POST':
		switch ( $device = $_POST['device'] ?? '' ) {
			case 'apple-mobileconfig': $generator = $realm->getConfigGenerator( \letswifi\profile\generator\MobileConfigGenerator::class, $user ); break;
			case 'eap-config': $generator = $realm->getConfigGenerator( \letswifi\profile\generator\EapConfigGenerator::class, $user ); break;
			case 'pkcs12': $generator = $realm->getConfigGenerator( \letswifi\profile\generator\PKCS12Generator::class, $user ); break;
			default:
				\header( 'Content-Type: text/plain', true, 400 );
				exit( "400 Bad Request\r\n\r\nUnknown device ${device}\r\n" );
		}
		$payload = $generator->generate();
		\header( 'Content-Disposition: attachment; filename="' . $generator->getFilename() . '"' );
		\header( 'Content-Type: ' . $generator->getContentType() );
		exit( $payload );
	default:
		\header( 'Content-Type: text/plain', true, 405 );
		exit( "405 Method Not Allowed\r\n" );
}
