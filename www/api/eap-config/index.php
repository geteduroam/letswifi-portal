<?php declare(strict_types=1);
// Hack, https://github.com/geteduroam/ionic-app/issues/9
if (strpos($_SERVER['QUERY_STRING'], '?')) {
    parse_str(strtr($_SERVER['QUERY_STRING'],'?','&'), $_GET);
}

require implode(DIRECTORY_SEPARATOR, [dirname(__DIR__, 3), 'src', '_autoload.php']);

// The old ionic app uses GET here, so allow for now to keep compatibility
// The current ionic app does this OK, so no issue link
/*
if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
	header( 'Content-Type: text/plain', true, 405 );
	die( "405 Method Not Allowed\r\n\r\nOnly POST is allowed for this resource\r\n" );
}
*/

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();
$realm = $app->getRealm();
$oauth = $app->getOAuthHandler( $realm );
$token = $oauth->getAccessTokenFromRequest( 'eap-metadata' );
$grant = $token->getGrant();
$user = new letswifi\realm\User( $grant->getSub() );
$generator = $realm->getUserEapConfig( $user );
$payload = $generator->generate();

// Hack, https://github.com/geteduroam/ionic-app/issues/31
if ( $grant->getClientId() === '07dc14f4-62d1-400a-a25b-7acba9bd7773' ) {
	$payload = str_replace( '<ClientCertificate format="PKCS12" encoding="base64">', '<ClientCertificate>', $payload );
}

header( 'Content-Type: ' . $generator->getContentType() );
echo $payload;
