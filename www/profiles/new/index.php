<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2020, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * SPDX-License-Identifier: BSD-3-Clause
 */

require \implode(\DIRECTORY_SEPARATOR, [\dirname(__DIR__, 3), 'src', '_autoload.php']);

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();
$realm = $app->getRealm();
$browserAuth = $app->getBrowserAuthenticator( $realm );
$sub = $browserAuth->requireAuth();
$user = new letswifi\realm\User( $sub );

switch ( $_SERVER['REQUEST_METHOD'] ) {
	case 'GET': return $app->render(
		[
			'href' => '/profile/new/',
			'devices' => [
				'apple-mobileconfig' => [
					'name' => 'Apple (iOS/MacOS)',
				],
				'eap-config' => [
					'name' => 'eap-config',
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
			default:
				\header( 'Content-Type: text/plain', true, 400 );
				die( "400 Bad Request\r\n\r\nUnknown device ${device}\r\n" );
		}
		$payload = $generator->generate();
		\header( 'Content-Disposition: attachment; filename="' . $generator->getFilename() . '"' );
		\header( 'Content-Type: ' . $generator->getContentType() );
		die( $payload );
	default:
		\header( 'Content-Type: text/plain', true, 405 );
		die( "405 Method Not Allowed\r\n" );
}
