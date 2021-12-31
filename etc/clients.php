<?php return [
	# Shell script test application
	'app.geteduroam.sh' => [
		'clientId' => 'app.geteduroam.sh',
		// We use mainly IPv4 because the nc binary might not use IPv6 by default
		'redirectUris' => ['http://[::1]/', 'http://127.0.0.1/'],
		'scopes' => ['eap-metadata'],
		'refresh' => true
	],

	# Windows application
	# https://github.com/geteduroam/windows-app
	'app.geteduroam.win' => [
		'clientId' => 'app.geteduroam.win',
		// Windows supports IPv6 just fine, so the IPv4 version might not be needed
		'redirectUris' => ['http://[::1]/', 'http://127.0.0.1/'],
		'scopes' => ['eap-metadata'],
		'refresh' => true
	],

	# Mobile application
	# https://github.com/geteduroam/ionic-app/pull/47
	'app.eduroam.geteduroam' => [
		'clientId' => 'app.eduroam.geteduroam',
		'redirectUris' => ['app.eduroam.geteduroam:/'],
		'scopes' => ['eap-metadata']
	],
];
