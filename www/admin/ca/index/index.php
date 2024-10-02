<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 4 ), 'src', '_autoload.php'] );

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();
$realm = $app->getRealm();

if ( \PHP_SAPI === 'cli' ) {
	$realmName = $argv[1];
	$caName = $argv[2];
	if ( !$caName ) {
		echo "Usage: {$argv[0]} realm ca-name\n";
		exit( 1 );
	}
} else {
	\assert( \array_key_exists( 'REQUEST_METHOD', $_SERVER ) ); // Psalm
	$app->requireAdmin( 'admin-ca-index' );

	if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
		\header( 'Content-Type: text/plain', true, 405 );
		exit( "405 Method Not Allowed\r\n\r\nOnly POST is allowed for this resource\r\n" );
	}

	$oauth = $app->getOAuthHandler( $realm );
	$token = $oauth->getAccessTokenFromRequest( 'admin-ca-index' );
	$grant = $token->getGrant();

	$caName = $_POST['ca'];
	$realmName = $realm->getName();
}

if ( !\is_string( $caName ) ) {
	\header( 'Content-Type: text/plain', true, 400 );
	exit( "400 Bad Request\r\n\r\nExpected POST parameter \"ca\"\r\n" );
}

$realmManager = $app->getRealmManager();
$result = [];
$utc = new DateTimeZone( 'UTC' );
$now = new DateTimeImmutable( 'now', $utc );
$credentials = $realmManager->getNonexpiredClientCredentials( $realmName, $caName, 'client' );

foreach ( $credentials as $log ) {
	$revoked = null === $log['revoked']
		? null
		: DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $log['revoked'], $utc );
	$expires = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $log['expires'], $utc );

	\assert( false !== $revoked, 'revocation date is well-formed' );
	\assert( $expires instanceof DateTimeImmutable, 'expiry is set' );

	$entryType = 'V'; // Valid
	if ( $now->getTimestamp() >= $expires->getTimestamp() ) {
		$entryType = 'E'; // Expired
	}
	if ( null !== $revoked ) {
		\assert(
			$now->getTimestamp() >= $revoked->getTimestamp(),
			'Don\'t know what to do with a certificate that is revoked in the future',
		);
		$entryType = 'R'; // Revoked
	}

	$serial = $log['serial'];
	if ( \strlen( $serial ) % 2 ) {
		// Serials must have an even length according to OpenSSL
		$serial = "0{$serial}";
	}

	// Create an OpenSSL-style index.txt listing
	$result[] = \sprintf(
		"%s\t%s\t%s\t%s\t%s\t%s\n", // 6 column TSV
		$entryType,
		$expires->format( 'ymdHis\\Z' ),
		null === $revoked ? '' : $revoked->format( 'ymdHis\\Z' ),
		$serial,
		'unknown', // path to the certificate file, apparently never used
		$log['sub'],
	);
}

\header( 'Content-Type: text/plain' );
echo \implode( '', $result );
