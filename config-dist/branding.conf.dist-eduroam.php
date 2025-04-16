<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

return [
	'product_name' => 'geteduroam',
	'product_shortname' => 'geteduroam',
	'network_name' => 'eduroam',
	'css_file' => 'geteduroam.css',
	'favicon_file' => 'geteduroam.ico',

	'platforms' => [
		'android' => [
			'name' => 'Android',
			'match' => 'Android [1-9]',
			// You can add the Huawei AppGallery here as well,
			// but the app is out of date.
			// We recommend that you do not use it.
			'apps' => ['fdroid', 'playstore'],
		],
		'chromebook' => [
			'name' => 'ChromeOS',
			'match' => 'CrOS',
			'apps' => ['playstore'],
			'profiles' => ['google-onc'],
		],
		'ios' => [
			'name' => 'iOS',
			'match' => '(iPad|iPhone|iPod);.*OS [1-9]_',
			'apps' => ['appstore'],
			'profiles' => ['apple-mobileconfig'],
		],
		'linux' => [
			'name' => 'Linux',
			'match' => 'Linux',
			'apps' => ['linux'],
		],
		'macos' => [
			'name' => 'macOS',
			'match' => 'Mac OS X [1-9][0-9][._][0-9]',
			'profiles' => ['apple-mobileconfig'],
		],
		'windows' => [
			'name' => 'Windows',
			'match' => 'Windows NT [0-9]',
			'apps' => ['winnt-amd64', 'winnt-arm64'],
		],
	],

	'apps' => [
		'appstore' => [
			'name' => 'App Store',
			'href' => 'https://apps.apple.com/app/geteduroam/id1504076137',
			'type' => 'store',
		],
		'fdroid' => [
			'name' => 'F-Droid',
			'href' => 'https://f-droid.org/en/packages/app.eduroam.geteduroam',
			'type' => 'store',
		],
		'huawei' => [
			// The app in the Huawei AppGallery is outdated.
			// We recommend that you do not use it.
			// Devices without Google Play can use F-Droid.
			'name' => 'Huawei AppGallery',
			'href' => 'https://appgallery.huawei.com/app/C104231893',
			'type' => 'store',
		],
		'linux' => [
			'name' => 'GitHub Release',
			'href' => 'https://github.com/geteduroam/linux-app/releases',
			'type' => 'link',
		],
		'playstore' => [
			'name' => 'Play Store',
			'href' => 'https://play.google.com/store/apps/details?id=app.eduroam.geteduroam',
			'type' => 'store',
		],
		'winnt-amd64' => [
			'name' => 'Intel/AMD',
			'href' => 'https://dl.eduroam.app/windows/amd64/geteduroam.exe',
			'type' => 'download',
		],
		'winnt-arm64' => [
			'name' => 'ARM',
			'href' => 'https://dl.eduroam.app/windows/arm64/geteduroam.exe',
			'type' => 'download',
		],
	],

	'profiles' => [
		'google-onc' => [
			'name' => 'ONC Profile',
		],
		'apple-mobileconfig' => [
			'name' => 'Apple Configuration Profile',
		],
	],
];
