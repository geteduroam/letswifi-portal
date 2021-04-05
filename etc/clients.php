<?php return [
	# Shell script test application
	[
		'clientId' => 'app.geteduroam.sh',
		// No IPv6 because the nc binary might not support it
		'redirectUris' => ['http://127.0.0.1/'],
		'scopes' => ['eap-metadata'],
		'refresh' => true
	],

	# Windows application
	# https://github.com/geteduroam/windows-app
	[
		'clientId' => 'app.geteduroam.win',
		'redirectUris' => ['http://[::1]/'],
		'scopes' => ['eap-metadata'],
		'refresh' => true
	],

	# Mobile application
	# https://github.com/geteduroam/ionic-app/pull/47
	[
		'clientId' => 'app.eduroam.geteduroam',
		'redirectUris' => ['app.eduroam.geteduroam:/'],
		'scopes' => ['eap-metadata']
	],
];
