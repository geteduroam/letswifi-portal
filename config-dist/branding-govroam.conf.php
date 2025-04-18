<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

return [
	'product_name' => 'getgovroam',
	'product_shortname' => 'getgovroam',
	'network_name' => 'govroam',
	'css_file' => 'getgovroam.css',
	'favicon_file' => 'getgovroam.ico',

	'platforms' => [
		'android' => [
			'name' => 'Android',
			'match' => 'Android [1-9]',
			'apps' => ['play-store', /*'f-droid'*/],
		],
		'chromebook' => [
			'name' => 'ChromeOS',
			'match' => 'CrOS',
			'apps' => ['play-store'],
			'profiles' => ['google-onc'],
		],
		'ios' => [
			'name' => 'iOS',
			'match' => '(iPad|iPhone|iPod);.*OS [1-9][0-9]+_[0-9]',
			'apps' => ['app-store'],
			'profiles' => ['apple-mobileconfig'],
		],
		/*
		'linux' => [
			'name' => 'Linux',
			'match' => 'Linux',
			'apps' => ['linux'],
		],
		*/
		'macos' => [
			'name' => 'macOS',
			'match' => 'Mac OS X [1-9][0-9][._][0-9]',
			'profiles' => ['apple-mobileconfig'],
		],
		'windows' => [
			'name' => 'Windows',
			'match' => 'Windows NT [0-9]',
			'apps' => [/*'microsoft-store',*/ 'winnt-amd64'/*, 'winnt-arm64'*/],
		],
	],

	'apps' => [
		'app-store' => [
			'name' => 'App Store',
			'href' => 'https://apps.apple.com/app/getgovroam/id1570235475',
			'type' => 'store',
		],
		/*
		'f-droid' => [
			'name' => 'F-Droid',
			'type' => 'store',
			'href' => 'https://f-droid.org/en/packages/app.eduroam.geteduroam',
		],
		'linux' => [
			'name' => 'GitHub Release',
			'href' => 'https://github.com/geteduroam/linux-app/releases',
			'type' => 'link',
		],
		*/
		'play-store' => [
			'name' => 'Play Store',
			'href' => 'https://play.google.com/store/apps/details?id=app.govroam.getgovroam',
			'type' => 'store',
		],
		/*
		'microsoft-store' => [
			'name' => 'Microsoft Store',
			'href' => 'https://apps.microsoft.com/detail?mode=direct',
			'type' => 'store',
		],
		*/
		'winnt-amd64' => [
			'name' => 'x64 EXE',
			'href' => 'https://getgovroam.nl/app/getgovroam.exe',
			'type' => 'download',
		],
		/*
		'winnt-arm64' => [
			'name' => 'arm64 EXE',
			'href' => 'https://dl.eduroam.app/windows/arm64/geteduroam.exe',
			'type' => 'download',
		],
		*/
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
