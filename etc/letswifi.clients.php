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
		'redirectUris' => ['http://[::1]/', 'http://localhost/'], 
		'scopes' => ['eap-metadata'], 
		'refresh' => true
	],
	[
		# deprecated client
		'clientId' => 'f817fbcc-e8f4-459e-af75-0822d86ff47a', 
		'redirectUris' => ['http://[::1]/', 'http://localhost/'], 
		'scopes' => ['eap-metadata']
	],

	# Mobile application
	# https://github.com/geteduroam/ionic-app
	[
		'clientId' => '07dc14f4-62d1-400a-a25b-7acba9bd7773', 
		'redirectUris' => ['letswifi://auth_callback'], 
		'scopes' => ['eap-metadata']
	],
];
