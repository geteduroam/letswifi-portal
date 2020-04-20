<?php declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
	header( 'Content-Type: text/plain', true, 403 );
	die( "403 Forbidden\r\n\r\nThis script is intended to be run from the commandline only\r\n");
}
require implode(DIRECTORY_SEPARATOR, [dirname(__DIR__, 1), 'src', '_autoload.php']);

use fyrkat\openssl\CSR;
use fyrkat\openssl\DN;
use fyrkat\openssl\OpenSSLConfig;
use fyrkat\openssl\PrivateKey;

$app = new geteduroam\GetEduroamApp();
$app->registerExceptionHandler();
$realm = $app->getRealm( 'example.com' );

$caPrivKey = new PrivateKey( new OpenSSLConfig( OpenSSLConfig::KEY_EC ) );
$caCsr = CSR::generate(
		new DN( ['CN' => 'geteduroam example CA'] ), // Subject
		$caPrivKey // CA key
	);
$caCertificate = $caCsr->sign(
		null, // CA certificate
		$caPrivKey, // CA key
		18250, // Validity in days
		new OpenSSLConfig( OpenSSLConfig::X509_CA ) // EKU
	);

$realm->writeRealmData([
		'trustedCaCert' => $caCertificate->getX509Pem(),
		'trustedServerName' => 'radius.example.com',
		'signingCaCert' => $caCertificate->getX509Pem(),
		'signingCaKey' => $caPrivKey->getPrivateKeyPem( null ),
	]);
