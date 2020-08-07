<?php return [
	'auth.service' => 'SimpleSAMLFeideAuth',
	'auth.params' => [
			'autoloadInclude' => dirname( __DIR__ ) . '/simplesamlphp/lib/_autoload.php',
			'authSource' => 'default-sp',
		],
	'realm.auth' => [
			'uninett.geteduroam.no' => [
					'userIdAttribute' => 'eduPersonPrincipalName',
					'homeOrgAttribute' => 'schacHomeOrganization',
					'samlIdp' => 'https://idp-test.feide.no',
					'feideHomeOrg' => 'uninett.no',
					'feideHostname' => 'idp-test.feide.no',
				],
			'demo.eduroam.no' => [
					'userIdAttribute' => 'eduPersonPrincipalName',
					'samlIdp' => 'https://idp-test.feide.no',
				],
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
