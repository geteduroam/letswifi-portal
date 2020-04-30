#!/usr/bin/env php
<?php declare(strict_types=1);
if ( PHP_SAPI !== 'cli' ) {
	header( 'Content-Type: text/plain', true, 403 );
	die( "403 Forbidden\r\n\r\nThis script is intended to be run from the commandline only\r\n");
}
if ( sizeof( $argv ) !== 2 ) {
	echo "init-db.php realm [common_name]\n";
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

$caPrivKey = new PrivateKey( new OpenSSLConfig( OpenSSLConfig::KEY_EC ) );
$caCsr = CSR::generate(
		new DN( ['CN' => $argv[2] ?? ( $argv[1] . ' Let\'s Wi-Fi CA' )] ), // Subject
		$caPrivKey // CA key
	);
$caCertificate = $caCsr->sign(
		null, // CA certificate
		$caPrivKey, // CA key
		18250, // Validity in days
		new OpenSSLConfig( OpenSSLConfig::X509_CA ) // EKU
	);

$realm->writeRealmData( [
		'trustedCaCert' => $caCertificate->getX509Pem(),
		'trustedServerName' => 'radius.' . $argv[1],
		'signingCaCert' => $caCertificate->getX509Pem(),
		'signingCaKey' => $caPrivKey->getPrivateKeyPem( null ),
		'secretKey' => random_bytes( 32 ),
	] );
