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

if ( $_SERVER['REQUEST_METHOD'] === 'GET' ) {
	switch ( $device = $_GET['device'] ?? '' ) {
		case 'apple-mobileconfig-download':
			$generator = $realm->getConfigGenerator( \letswifi\profile\generator\MobileConfigGenerator::class, $user );
			$payload = $generator->generate();
			\header( 'Content-Disposition: attachment; filename="' . $generator->getFilename() . '"' );
			\header( 'Content-Type: ' . $generator->getContentType() );
			exit( $payload );

		case 'apple-mobileconfig':
			$_GET['device'] = 'apple-mobileconfig-download';
			$payload = '<!DOCTYPE html>';
			$payload .= '<html lang="en" class="3col">';
			$payload .= '<link rel="stylesheet" href="/assets/geteduroam.css">';
			$payload .= '<link rel="icon" href="/assets/geteduroam.ico" type="image/x-icon">';
			$payload .= '<meta name="viewport" content="width=device-width, initial-scale=1">';
			$payload .= '<meta http-equiv="Refresh" content="1; URL=' . $_SERVER['SCRIPT_NAME'] . '?' . \http_build_query($_GET) . '">';
			$payload .= '<title>geteduroam - Apple mobileconfig</title>';
			$payload .= '<nav></nav>';
			$payload .= '<main>';
			$payload .= '<h1>geteduroam<small>– eduroam authentication made easy</small></h1>';
			$payload .= '<p>Your download will begin shortly</p>';
			$payload .= '<p>If you have macOS Big Sur, please go to Profiles in System Preferences to complete installation</p>';
			$payload .= '</main>';
			exit( $payload );

		default:
			return $app->render(
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
		}
}

\header( 'Content-Type: text/plain', true, 405 );
exit( "405 Method Not Allowed\r\n" );
