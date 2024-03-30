<?php return [
	'auth.service' => 'SimpleSAMLAuth',
	'auth.admin' => [
		//'eu.letswifi.cli', // A client ID with clientSecret
		//'jornane', // A NameID or userIdAttribute
	],
	'auth.params' => [
		'autoloadInclude' => '/usr/share/php/simplesamlphp/lib/_autoload.php',
		'authSource' => 'default-sp',
	],
	'realm.selector' => null, // one of null, getparam or httphost
	'realm.default' => 'example.com', // used when realm.selector = null
	'realm.auth' => [
		// Generic example with SimpleSAMLphp
		'example.com' => [
			'userIdAttribute' => 'eduPersonPrincipalName', // null for NameID
			'samlIdp' => 'https://idp.example.com',
		],
		// SimpleSAMLphp with SURFconext
		'example.eduroam.nl' => [
			'userIdAttribute' => 'eduPersonPrincipalName', // null for NameID
			'samlIdp' => 'https://engine.test.surfconext.nl/authentication/idp/metadata',
			'idpList' => [
					'https://example.com' // your institution in SURFconext
				],
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
			//],
		],
	],
	'pdo.dsn' => 'sqlite:/var/lib/letswifi/letswifi.sqlite',
	'pdo.username' => null,
	'pdo.password' => null,
	//'signing.cert' => __DIR__ . DIRECTORY_SEPARATOR . 'signing.pem',
	'oauth.clients' => (require __DIR__ . DIRECTORY_SEPARATOR . 'clients.php') + [
		//[
		//	'clientId' => 'com.example', 
		//	'redirectUris' => ['http://[::1]/callback/'], 
		//	'scopes' => ['eap-metadata', 'testscope'],
		//	'refresh' => false,
		//	// uncomment for client_credentials flow, and remove the "
		//	//'clientSecret' => '"s3cret',
		//],
	],
];
