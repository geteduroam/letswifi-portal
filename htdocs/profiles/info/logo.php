<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

use letswifi\LetsWifiApp;

require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 3 ), 'src', '_autoload.php'] );
$basePath = '../..';

$app = new LetsWifiApp( basePath: $basePath );
$app->registerExceptionHandler();
$provider = $app->getProvider();
$profileInfo = $provider->getContact();
$logo = $profileInfo?->logo;
if ( null !== $logo ) {
	$logo->emit();
}
\header( 'Content-Type: text/plain', true, 404 );

exit( "HTTP 404 Not Found\r\n\r\nNo logo configured for this provider.\r\n" );
