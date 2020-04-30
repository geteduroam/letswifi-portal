#!/usr/bin/env php
<?php declare(strict_types=1);
if ( PHP_SAPI !== 'cli' ) {
	header( 'Content-Type: text/plain', true, 403 );
	die( "403 Forbidden\r\n\r\nThis script is intended to be run from the commandline only\r\n");
}
if ( sizeof( $argv ) !== 3 && sizeof( $argv ) !== 4 ) {
	echo "serversign.php realm domain [days_valid]\n";
	die( 2 );
}
require implode(DIRECTORY_SEPARATOR, [dirname(__DIR__, 1), 'src', '_autoload.php']);

use fyrkat\openssl\CSR;
use fyrkat\openssl\DN;
use fyrkat\openssl\OpenSSLConfig;
use fyrkat\openssl\PrivateKey;

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();
$realm = $app->getRealm( $argv[1] );

$pkcs12 = $realm->generateServerCertificate( $argv[2], sizeof( $argv ) === 4 ? $argv[3] : 1095 );
echo $pkcs12->getPrivateKey()->getPrivateKeyPem( null );
echo $pkcs12->getX509();
