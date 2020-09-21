<?php return [
	'auth.service' => 'BasicAuth',
	'auth.params' => [
		'admin' => 'admin123ABC',
	],
	'pdo.dsn' => 'sqlite:' . dirname( __DIR__ ) . '/var/letswifi-dev.sqlite',
	'oauth.clients' => (require __DIR__ . DIRECTORY_SEPARATOR . 'letswifi.clients.php') + [
			[
				'clientId' => 'no.fyrkat.oauth', 
				'redirectUris' => ['http://[::1]/callback/'], 
				'scopes' => ['eap-metadata', 'testscope'],
				'refresh' => false,
			],
		],
];
