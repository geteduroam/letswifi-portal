<?php return [
    'auth.service' => 'SimpleSAMLAuth',
    'auth.params' => [
      'autoloadInclude' => dirname( __DIR__ ) . '/simplesamlphp/lib/_autoload.php',
      'authSource' => 'default-sp',
    ],
    'realm.selector' => 'httphost', // one of null, getparam or httphost
    'realm.default' => 'get.eduroam.my', // used when realm.selector = null
    'realm.auth' => [
      'get.eduroam.my' => [
        'userIdAttribute' => 'mail',
          #'authzAttributeValue' => [
          #    'urn:oid:1.3.6.1.4.1.5923.1.1.1.1' => ['member','staff','student']
          #],
      ],
    ],
    'pdo.dsn' => 'mysql:host=<mysql server hostname>;dbname=<mysql db name>',
    'pdo.username' => '<mysql db username>',
    'pdo.password' => '<mysql db password>',
    # You can use this command to convert your multiline PEM file to a single line: awk 'NF {sub(/\r/, ""); printf "%s\\n",$0;}' < your.pem >
    'profile.signing.cert' => "",
    'profile.signing.key' => "",
    'profile.signing.ca' => [],
    'profile.signing.passphrase' => '',
    'auth.admin-ca-index' => [ '' ],
    'auth.admin-ca-revoke' => [ '' ],
    'oauth.clients' => (require __DIR__ . DIRECTORY_SEPARATOR . 'clients.php')
    /*
    'oauth.clients' => (require __DIR__ . DIRECTORY_SEPARATOR . 'clients.php') + [
      [
        'clientId' => 'app.geteduroam.sh',
        'redirectUris' => ['http://[::1]/callback/'],
        'scopes' => ['eap-metadata', 'testscope'],
        'refresh' => false,
      ],
    ],
     */
];
