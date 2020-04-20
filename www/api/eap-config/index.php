<?php declare(strict_types=1);
require implode(DIRECTORY_SEPARATOR, [dirname(__DIR__, 3), 'src', '_autoload.php']);

if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
	header( 'Content-Type: text/plain', true, 405 );
	die( "405 Method Not Allowed\r\n\r\nOnly POST is allowed for this resource\r\n" );
}

$app = new geteduroam\GetEduroamApp();
$app->registerExceptionHandler();
$realm = $app->getRealm();
$oauth = $app->getOAuthHandler( $realm );
$token = $oauth->getAccessTokenFromRequest();
$user = new geteduroam\User( $token->getSubject() );
$generator = $realm->getUserEapConfig( $user, (new DateTime())->add( new DateInterval( 'P1D' ) ) );
$payload = $generator->generate();
header( 'Content-Type: ' . $generator->getContentType() );
echo $payload;
