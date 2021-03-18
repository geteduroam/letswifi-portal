<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2021, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * Copyright: 2020-2021, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

require \implode(\DIRECTORY_SEPARATOR, [\dirname(__DIR__, 3), 'src', '_autoload.php']);

// The old ionic app uses GET here, so allow for now to keep compatibility
// The current ionic app does this OK, so no issue link
$usedGetButShouldHaveUsedPost = 'POST' !== $_SERVER['REQUEST_METHOD'];

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();
$realm = $app->getRealm();
$oauth = $app->getOAuthHandler( $realm );
$token = $oauth->getAccessTokenFromRequest( 'eap-metadata' );
$grant = $token->getGrant();
$sub = $grant->getSub();
if ( null === $sub ) {
	\header( 'Content-Type: text/plain', true, 403 );
	exit( "403 Forbidden\r\n\r\nNo user subject available\r\n" );
}
$user = new letswifi\realm\User( $sub );
$generator = $realm->getConfigGenerator( \letswifi\profile\generator\EapConfigGenerator::class, $user );
$payload = $generator->generate();

// Hack, fix clients that GET where they should POST
if ( \in_array( $grant->getClientId(),
		[
			// https://github.com/geteduroam/windows-app/issues/27, fixed in 61127d8
			// but keep allowing while old clients are still in rotation
			'app.geteduroam.win',
		], true )
) {
	// These clients GET instead, so it's expected
	$usedGetButShouldHaveUsedPost = false;
}

if ( $usedGetButShouldHaveUsedPost ) {
	\header( 'Content-Type: text/plain', true, 405 );
	exit( "405 Method Not Allowed\r\n\r\nOnly POST is allowed for this resource\r\n" );
}

\header( 'Content-Type: ' . $generator->getContentType() );
echo $payload;
