<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

use letswifi\LetsWifiApp;
use letswifi\error\HttpMethodException;

// This file is still here for compatibility with old clients

if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? null ) ) {
	// We were planning on just including another file and letting it handle all startup formalities
	// but since we already have to handle an error here, just do a quick startup sequence here
	require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 3 ), 'autoload.php'] );
	$app = new LetsWifiApp( urlRelativeBase: '../..' );

	throw new HttpMethodException( ['POST'] );
}

$_GET['format'] ??= 'eap-config';

require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 2 ), 'profiles', 'new', 'index.php'] );
