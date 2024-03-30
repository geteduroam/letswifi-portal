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
$basePath = '../..';

const POST_FIELD = 'approve';
const POST_VALUE = 'yes';

// Test this file by serving it on http://[::1]:1080/oauth/authorize/ and point your browser to:
// http://[::1]:1080/oauth/authorize/?response_type=code&code_challenge_method=S256&scope=testscope&code_challenge=E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM&redirect_uri=http://[::1]:1234/callback/&client_id=no.fyrkat.oauth&state=0

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();
$realm = $app->getRealm();
$oauth = $app->getOAuthHandler( $realm );

$oauth->assertAuthorizeRequest();
$browserAuth = $app->getBrowserAuthenticator( $realm );

$user = $app->getUserFromBrowserSession( $realm );
try {
	if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
		$oauth->handleAuthorizePostRequest( new fyrkat\oauth\token\Grant(
			[
				'sub' => $user->getUserId(),
				'realm' => $user->getRealm(),
			],
		), POST_VALUE === $_POST[POST_FIELD] );

		// handler should never return, this code should be unreachable
		\header( 'Content-Type: text/plain' );
		exit( "500 Internal Server Error\r\n\r\nServer error: OAuth POST request was not handled\r\n" );
	}

	$app->render( [
		'realmName' => $realm->getName(),
		'logoutUrl' => $browserAuth->getLogoutUrl(),
		'userId' => $user->getUserID(),
		'postField' => POST_FIELD,
		'postValue' => POST_VALUE,
	], 'authorize', $basePath );
} catch ( letswifi\browserauth\MismatchIdpException $e ) {
	\header( 'Content-Type: text/plain' );
	\printf( "403 Forbidden\r\n\r\nRealm %s is not valid for user %s", $realm->getName(), $user->getUserID() );
	exit;
}
