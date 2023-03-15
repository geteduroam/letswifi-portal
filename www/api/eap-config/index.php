<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2022, Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: 2020-2022, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

require \implode(\DIRECTORY_SEPARATOR, [\dirname(__DIR__, 3), 'src', '_autoload.php']);
\assert( \array_key_exists( 'REQUEST_METHOD', $_SERVER ) ); // Psalm

if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
	\header( 'Content-Type: text/plain', true, 405 );
	exit( "405 Method Not Allowed\r\n\r\nOnly POST is allowed for this resource\r\n" );
}

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();
$realm = $app->getRealm();
$oauth = $app->getOAuthHandler( $realm );
$token = $oauth->getAccessTokenFromRequest( 'eap-metadata' );
$grant = $token->getGrant();

$user = $app->getUserFromGrant( $grant );
$format = \array_key_exists( 'format', $_GET ) ? \is_string( $_GET['format'] ) ? $_GET['format'] : null : null;
switch ( $format ?? 'eap-config' ) {
	case 'eap-config':
		$generator = $realm->getConfigGenerator( \letswifi\profile\generator\EapConfigGenerator::class, $user );
		break;
	case 'mobileconfig':
		$generator = $realm->getConfigGenerator( \letswifi\profile\generator\MobileConfigGenerator::class, $user );
		break;
	default:
		\header( 'Content-Type: text/plain', true, 400 );
		exit( "400 Bad Request\r\n\r\nUnknown format: {$format}\r\n" );
}
$payload = $generator->generate();

\header( 'Content-Type: ' . $generator->getContentType() );
echo $payload;
