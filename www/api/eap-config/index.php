<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2021, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * Copyright: 2020-2021, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

require \implode(\DIRECTORY_SEPARATOR, [\dirname(__DIR__, 3), 'src', '_autoload.php']);

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
$generator = $realm->getConfigGenerator( \letswifi\profile\generator\EapConfigGenerator::class, $user );
$payload = $generator->generate();

\header( 'Content-Type: ' . $generator->getContentType() );
echo $payload;
