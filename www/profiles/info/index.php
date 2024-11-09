<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
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
$profileInfo = (array)$provider->getContact();
$profileInfo['displayName'] = $provider->displayName;
$profileInfo['description'] = $provider->description;

return $app->render(
	[
		'href' => "{$basePath}/profiles/info/",
		'http://letswifi.app/profile#v2' => $profileInfo,
	], null, $basePath );
