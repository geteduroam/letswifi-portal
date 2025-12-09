<?php return [
	'auth.service' => 'ShibbolethAuth',
	'auth.admin' => [],
	'auth.params' => [
		//'shibHandlerUrl' => '/saml2',
		//'userIdAttribute' => 'eppn',
	],
	//'realm.selector' => 'getparam', // one of null, getparam or httphost
	//'realm.selector' => 'null', // one of null, getparam or httphost
	'realm.selector' => 'httphost', // one of null, getparam or httphost
	//'realm.default' => 'demo.eduroam.nl', // used when realm.selector = null
	'realm.default' => '', // used when realm.selector = null
	'realm.auth' => [
			'demo.eduroam.nl' => [
				'samlIdp' => 'https://engine.surfconext.nl/authentication/idp/metadata',
				//'shibHandlerUrl' => '/Shibboleth.sso',
				//'userIdAttribute' => 'persistent-id',
				//'authzAttributeValue' => [
				//      'affiliation' => ['employee','staff'],
				//      'unscoped-affiliation' => ['employee','staff'],
				//      'eduPersonAffiliation' => ['employee','staff'],
				//      'eduPersonEntitlement' => 'geteduroam-user',
				//      ],
				//'userRealmPrefixAttribute' => 'primary-affiliation',
				//'userRealmPrefixAttribute' => 'affiliation',
				//'userRealmPrefixAttribute' => 'unscoped-affiliation',
				//'userRealmPrefixValueMap' => [
				//	'employee' => null,
                                //      'staff' => null,
                                //      'student' => 'student',
				//	'*' => null,
                                //      ],
				],
			'demo2.eduroam.nl' => [
				'userIdAttribute' => 'REMOTE_USER', //default
				],
		],
	'pdo.dsn' => 'sqlite:' . dirname( __DIR__ ) . '/var/letswifi.sqlite',
	'pdo.username' => null,
	'pdo.password' => null,
        'oauth.clients' => (require __DIR__ . DIRECTORY_SEPARATOR . 'clients.php') + [
                        [
                                'clientId' => 'nl.geteduroam.oauth',
                                'redirectUris' => ['http://[::1]/callback/'],
                                'scopes' => ['eap-metadata', 'testscope'],
                                'refresh' => false,
                        ],
                ],
];
