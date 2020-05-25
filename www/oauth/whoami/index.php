<?php declare(strict_types=1);
// Hack, https://github.com/geteduroam/ionic-app/issues/9
if (strpos($_SERVER['QUERY_STRING'], '?')) {
    parse_str(strtr($_SERVER['QUERY_STRING'],'?','&'), $_GET);
}

require implode(DIRECTORY_SEPARATOR, [dirname(__DIR__, 3), 'src', '_autoload.php']);

// Ensure you send a access_token in POST or a Bearer
// Fetch your access_token by running token/index.php
// access_token=…
//
// curl -iHAuthorization:Bearer\ $access_token 'http://[::1]:1080/oauth/whoami/'

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();
$realm = $app->getRealm();
$oauth = $app->getOAuthHandler( $realm );

header( 'Content-Type: text/plain' );
die( 'Subject: ' . $oauth->getAccessTokenFromRequest( null )->getSubject() . "\n" );
