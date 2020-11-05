<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2020, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * SPDX-License-Identifier: BSD-3-Clause
 */

if (\strpos($_SERVER['QUERY_STRING'], '?')) {
	\parse_str(\strtr($_SERVER['QUERY_STRING'], '?', '&'), $_GET);
}

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
	die( "403 Forbidden\r\n\r\nNo user subject available\r\n" );
}
$user = new letswifi\realm\User( $sub );
$generator = $realm->getUserEapConfig( $user );
$payload = $generator->generate();

// Hack, fix clients that care about the ordering of <IEEE80211> elementsHack
if ( \in_array( $grant->getClientId(),
	[
		// https://github.com/geteduroam/ionic-app/issues/31
		'f817fbcc-e8f4-459e-af75-0822d86ff47a',
	], true )
) {
	$payload = \str_replace(
			// The ionic app fails if the ConsortiumOID is before the SSID
			"\t\t\t<IEEE80211>\r\n\t\t\t\t<ConsortiumOID>001bc50460</ConsortiumOID>\r\n\t\t\t</IEEE80211>\r\n\t\t\t<IEEE80211>\r\n\t\t\t\t<SSID>eduroam</SSID>\r\n\t\t\t\t<MinRSNProto>CCMP</MinRSNProto>\r\n\t\t\t</IEEE80211>",
			"\t\t\t<IEEE80211>\r\n\t\t\t\t<SSID>eduroam</SSID>\r\n\t\t\t\t<MinRSNProto>CCMP</MinRSNProto>\r\n\t\t\t</IEEE80211>\r\n\t\t\t<IEEE80211>\r\n\t\t\t\t<ConsortiumOID>001bc50460</ConsortiumOID>\r\n\t\t\t</IEEE80211>",
			$payload
		);
}

// Hack, fix clients that don't understand attributes in <ClientCertificate>
if ( \in_array( $grant->getClientId(),
	[
		// https://github.com/geteduroam/ionic-app/issues/51
		'f817fbcc-e8f4-459e-af75-0822d86ff47a',
	], true )
) {
	$payload = \str_replace(
			// The ionic app fails when 'format="PKCS12" encoding="base64" is present
			'<ClientCertificate format="PKCS12" encoding="base64">',
			'<ClientCertificate>',
			$payload
		);
}

// Hack, fix clients that GET where they should POST
if ( \in_array( $grant->getClientId(),
		[
			// https://github.com/geteduroam/ionic-app/issues/50
			'app.geteduroam.ionic',

			// https://github.com/geteduroam/ionic-app/issues/50
			'f817fbcc-e8f4-459e-af75-0822d86ff47a',

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
	die( "405 Method Not Allowed\r\n\r\nOnly POST is allowed for this resource\r\n" );
}

\header( 'Content-Type: ' . $generator->getContentType() );
echo $payload;
