<?php return [
	'auth.service' => 'BasicAuth',
	'auth.admin' => [
		//'eu.letswifi.cli', // A client ID with clientSecret
		//'jornane', // A NameID or userIdAttribute
	],
	'auth.params' => [
		'admin' => 'admin123ABC',
	],
	'realm.selector' => null, // one of null or httphost
	'realm.default' => 'demo.eduroam.no', // used when realm.selector = null
	'realm.auth' => [
			'demo.eduroam.no' => [], // No settings needed
		],
	'pdo.dsn' => 'sqlite:' . dirname( __DIR__ ) . '/var/letswifi-dev.sqlite',
	'pdo.username' => null,
	'pdo.password' => null,
	'oauth.clients' => (require __DIR__ . DIRECTORY_SEPARATOR . 'clients.php') + [
			[
				'clientId' => 'no.fyrkat.oauth', 
				'redirectUris' => ['http://[::1]/callback/'], 
				'scopes' => ['eap-metadata', 'testscope'],
				'refresh' => false,
				// uncomment for client_credentials flow, and remove the "
				//'clientSecret' => '"s3cret',
			],
		],
];
