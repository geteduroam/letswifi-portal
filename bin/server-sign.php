#!/usr/bin/env php
<?php declare(strict_types=1);
if ( PHP_SAPI !== 'cli' ) {
	header( 'Content-Type: text/plain', true, 403 );
	die( "403 Forbidden\r\n\r\nThis script is intended to be run from the commandline only\r\n");
}
if ( sizeof( $argv ) !== 3 && sizeof( $argv ) !== 4 ) {
	echo $argv[0] . " realm common_name [days_valid=1095]\n";
	die( 2 );
}
require implode(DIRECTORY_SEPARATOR, [dirname(__DIR__, 1), 'src', '_autoload.php']);

use fyrkat\openssl\CSR;
use fyrkat\openssl\DN;
use fyrkat\openssl\OpenSSLConfig;
use fyrkat\openssl\PrivateKey;

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();
$realmManager = $app->getRealmManager();
$user = new letswifi\realm\User( 'cli' );

$realm = $realmManager->getRealm( $argv[1] );
$pkcs12 = $realm->generateServerCertificate( $user, $argv[2], (new DateTime())->add( new DateInterval( 'P' . ( sizeof( $argv ) === 4 ? $argv[3] : 1095 ) . 'D' ) ) );
echo $pkcs12->getPrivateKey()->getPrivateKeyPem( null );
echo $pkcs12->getX509();
