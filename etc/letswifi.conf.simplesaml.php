<?php return [
	'auth.service' => 'SimpleSAMLAuth',
	'auth.admin' => [
		//'eu.letswifi.cli', // A client ID with clientSecret
		//'jornane', // A NameID or userIdAttribute
	],
	'auth.params' => [
			'autoloadInclude' => dirname( __DIR__ ) . '/simplesamlphp/src/_autoload.php',
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
					//'authzAttributeValue' => [
					//	'eduPersonAffiliation' => ['employee','staff'],
					//	'eduPersonEntitlement' => 'geteduroam-user',
					//	],
					//'verifyAuthenticatingAuthority' => false,
					//'userRealmPrefixAttribute' => 'eduPersonPrimaryAffiliation',
					//'userRealmPrefixAttribute' => 'eduPersonAffiliation',
					//'userRealmPrefixValueMap' => [
					//        'employee' => 'mdw',
					//        'staff' => null,
					//        'student' => 'student',
					//	],
					'idpList' => [
							'https://example.com'
						],
				],
		],
	'pdo.dsn' => 'sqlite:' . dirname( __DIR__ ) . '/var/letswifi-dev.sqlite',
	'pdo.username' => null,
	'pdo.password' => null,
	//'signing.cert' => __DIR__ . DIRECTORY_SEPARATOR . 'signing.pem',
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
