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
	# https://github.com/geteduroam/eduroam-supplicant-win
	[
		'clientId' => 'app.geteduroam.win',
		'redirectUris' => ['http://[::1]/'],
		'scopes' => ['eap-metadata'],
		'refresh' => true
	],
	[
		# deprecated Windows client
		# Also stolen by ionic-app?!??!!?!
		'clientId' => 'f817fbcc-e8f4-459e-af75-0822d86ff47a',
		'redirectUris' => [
			'http://[::1]/',
			'http://localhost:8080/'],
		'scopes' => ['eap-metadata']
	],
];
