<?php return [
	'platforms' => [
		'android' => [
			'name' => 'Android',
			'match' => 'Android [1-9]',
			'apps' => ['playstore', 'huawei'],
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
		/* Add when released
		'fdroid' => [
			'name' => 'F-Droid',
			'type' => 'store',
		],
		*/
		'huawei' => [
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
