<?php return [
	'auth.service' => 'SimpleSAMLAuth',
	'auth.params' => [
			'autoloadInclude' => dirname( __DIR__ ) . '/simplesamlphp/lib/_autoload.php',
			'authSource' => 'default-sp',
		],
	'realm.selector' => 'getparam', // one of null, getparam or httphost
	'realm.default' => 'demo.eduroam.no', // used when realm.selector = null
	'realm.auth' => [
			'demo.eduroam.no' => [
					'userIdAttribute' => 'eduPersonPrincipalName', // null for NameID
					'samlIdp' => 'https://idp-test.feide.no',
				],
			'demo.eduroam.nl' => [
					'userIdAttribute' => 'eduPersonPrincipalName', // null for NameID
					'samlIdp' => 'https://engine.test.surfconext.nl/authentication/idp/metadata',
					'idpList' => [
							'https://example.com'
						],
				],
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
			],
		],
];
