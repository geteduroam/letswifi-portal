<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

// This file is still here for compatibility with old clients

\assert( \array_key_exists( 'REQUEST_METHOD', $_SERVER ) ); // Psalm
if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
	\header( 'Content-Type: text/plain', true, 405 );

	exit( "405 Method Not Allowed\r\n\r\nOnly POST is allowed for this resource\r\n" );
}

$_GET['format'] ??= 'eap-config';

require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 2 ), 'profiles', 'new', 'index.php'] );
