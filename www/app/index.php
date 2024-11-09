<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

use letswifi\LetsWifiApp;

require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 2 ), 'src', '_autoload.php'] );
$basePath = '..';

$app = new LetsWifiApp( basePath: $basePath );
$app->registerExceptionHandler();

$app->render( [
	'href' => $basePath . '/app/',
	'apps' => [
		'android' => [
			'url' => 'https://play.google.com/store/apps/details?id=app.eduroam.geteduroam',
			'name' => 'Android',
		],
		'ios' => [
			'url' => 'https://apps.apple.com/app/geteduroam/id1504076137',
			'name' => 'iOS',
		],
		'windows' => [
			'url' => 'https://dl.eduroam.app/windows/x86_64/geteduroam.exe',
			'name' => 'Windows',
		],
		'huawei' => [
			'url' => 'https://appgallery.huawei.com/app/C104231893',
			'name' => 'Huawei',
		],
	],
	'os_config' => [
		'mobileconfig' => [
			'url' => "{$basePath}/profiles/mac/",
			'name' => 'macOS',
		],
		'onc' => [
			'url' => "{$basePath}/profiles/onc/",
			'name' => 'ChromeOS',
		],
	],
	'manual' => [
		'url' => "{$basePath}/profiles/new/",
	],
], 'app', $basePath );
