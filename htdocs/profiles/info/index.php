<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

use letswifi\LetsWifiApp;

require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 3 ), 'autoload.php'] );
$app = new LetsWifiApp( urlRelativeBase: '../..' );
$provider = $app->getProvider();
$profileInfo = (array)$provider->getContact();
$profileInfo['display_name'] = $provider->displayName;
$profileInfo['description'] = $provider->description;
if ( null !== $profileInfo['logo'] ) {
	// Override the logo object with an URL to the logo
	unset( $profileInfo['logo'] );
	$profileInfo['logo_endpoint'] = "{$app->getIndexUrl()}logo.php";
	\ksort( $profileInfo );
}
$app->render(
	[
		'http://letswifi.app/profile#v2' => $profileInfo,
	], null );
