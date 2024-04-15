<?php return [
	'auth.service' => 'SimpleSAMLFeideAuth',
	'auth.admin' => [
		//'eu.letswifi.cli', // A client ID with clientSecret
		//'jornane', // A NameID or userIdAttribute
	],
	'auth.params' => [
			'autoloadInclude' => dirname( __DIR__ ) . '/simplesamlphp/lib/_autoload.php',
			'authSource' => 'default-sp',
		],
	'realm.selector' => null, // one of null or httphost
	'realm.default' => 'demo.eduroam.no', // used when realm.selector = null
	'realm.auth' => [
			'uninett.geteduroam.no' => [
					'userIdAttribute' => 'eduPersonPrincipalName', // null for NameID
					'homeOrgAttribute' => 'schacHomeOrganization',
					'allowedHomeOrg' => 'uninett.no',
					'samlIdp' => 'https://idp-test.feide.no',
					'feideHostname' => 'idp-test.feide.no',
				],
			'demo.eduroam.no' => [
					'userIdAttribute' => 'eduPersonPrincipalName', // null for NameID
					'samlIdp' => 'https://idp-test.feide.no',
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
				// uncomment for client_credentials flow, and remove the "
				//'clientSecret' => '"s3cret',
			],
		],
];
