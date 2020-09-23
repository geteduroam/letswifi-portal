<?php return [
	'auth.service' => 'SimpleSAMLAuth',
	'auth.params' => [
			'autoloadInclude' => dirname( __DIR__ ) . '/simplesamlphp/lib/_autoload.php',
			'authSource' => 'default-sp',
		],
	'realm.auth' => [
			'demo.eduroam.no' => [
					'userIdAttribute' => 'eduPersonPrincipalName',
					'samlIdp' => 'https://idp-test.feide.no',
				],
			'letswifi.fyrkat.no' => [
					'userIdAttribute' => 'userPrincipalName',
					'samlIdp' => 'https://idp-test.feide.no',
				],
		],
	'pdo.dsn' => 'sqlite:' . dirname( __DIR__ ) . '/var/letswifi-dev.sqlite',
	'pdo.username' => null,
	'pdo.password' => null,
	'oauth.clients' => (require __DIR__ . DIRECTORY_SEPARATOR . 'letswifi.clients.php') + [
			[
				'clientId' => 'no.fyrkat.oauth', 
				'redirectUris' => ['http://[::1]/callback/'], 
				'scopes' => ['eap-metadata', 'testscope'],
				'refresh' => false,
			],
		],
];
