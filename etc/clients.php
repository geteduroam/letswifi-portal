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
	[
		# deprecated Windows client
		# Also clientId stolen by ionic-app?!??!!?!
		# https://github.com/geteduroam/ionic-app
		'clientId' => 'f817fbcc-e8f4-459e-af75-0822d86ff47a',
		'redirectUris' => [
			'http://[::1]/',
			'http://localhost:8080/'],
		'scopes' => ['eap-metadata']
	],

	# Mobile application
	# https://github.com/geteduroam/ionic-app/pull
	[
		'client_id' => 'app.geteduroam.ionic',
		'redirectUris' => [
			# Old client used http://localhost:8080 but we're not allowing that anymore
			'http://[::1]/', 'http://127.0.0.1/',

			# This was supposed to be the redirect for ionic-app, but it was never used
			# TODO Do we still need this?
			'letswifi://auth_callback',
		],
		'scopes' => ['eap-metadata']
	],
];
