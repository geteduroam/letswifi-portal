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
	'realm.default' => 'example.com',
	'pdo.dsn' => 'sqlite:' . dirname( __DIR__ ) . '/var/letswifi-dev.sqlite',
	'oauth.clients' => [
			['clientId' => 'f817fbcc-e8f4-459e-af75-0822d86ff47a', 'redirectUris' => ['http://localhost:8080/'], 'scopes' => ['eap-metadata']],
			['clientId' => '07dc14f4-62d1-400a-a25b-7acba9bd7773', 'redirectUris' => ['letswifi://auth_callback'], 'scopes' => ['eap-metadata']],
			['clientId' => 'no.fyrkat.oauth', 'redirectUris' => ['http://[::1]:1234/callback/'], 'scopes' => ['eap-metadata', 'testscope']],
		],
];
