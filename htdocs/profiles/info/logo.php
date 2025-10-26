<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

use letswifi\LetsWifiApp;
use letswifi\error\NotFoundException;

require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 3 ), 'src', '_autoload.php'] );
$app = new LetsWifiApp( basePath: '../..' );
$provider = $app->getProvider();
$logo = $provider->logo;

$realmId = $_GET['realm'] ?? null;
if ( \is_string( $realmId ) ) {
	$realm = $provider->getRealm( $realmId );
	$logo = null === $realm ? null : $realm->logo ?? $logo;
}

if ( null !== $logo ) {
	$logo->emit();
	// this should never be reached
}

throw new NotFoundException();
