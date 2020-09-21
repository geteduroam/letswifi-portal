#!/usr/bin/env php
<?php declare(strict_types=1);
if ( PHP_SAPI !== 'cli' ) {
	header( 'Content-Type: text/plain', true, 403 );
	die( "403 Forbidden\r\n\r\nThis script is intended to be run from the commandline only\r\n");
}
if ( sizeof( $argv ) !== 3 && sizeof( $argv ) !== 4 ) {
	// TODO make validity configurable
	echo "init-db.php realm client_cert_validity_days [common_name]\n";
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

$caPrivKey = new PrivateKey( new OpenSSLConfig( OpenSSLConfig::KEY_EC ) );
$caCsr = CSR::generate(
		new DN( ['CN' => $argv[3] ?? ( $argv[1] . ' Let\'s Wi-Fi CA' )] ), // Subject
		$caPrivKey // CA key
	);
$caCertificate = $caCsr->sign(
		null, // CA certificate
		$caPrivKey, // CA key
		18250, // Validity in days
		new OpenSSLConfig( OpenSSLConfig::X509_CA ) // EKU
	);

$realmManager->createRealm( $argv[1] );
$realmManager->importCA( $caCertificate, $caPrivKey );
$realmManager->addTrustedCa( $argv[1], $caCertificate->getSubject()->__toString() );
$realmManager->setSignerCa( $argv[1], $caCertificate->getSubject()->__toString(), new DateInterval( 'P' . $argv[2] . 'D' ) );
$realmManager->addServer( $argv[1], 'radius.' . $argv[1] );
