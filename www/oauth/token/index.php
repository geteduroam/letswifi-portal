<?php declare(strict_types=1);
// Hack, https://github.com/geteduroam/ionic-app/issues/9
if (strpos($_SERVER['QUERY_STRING'], '?')) {
    parse_str(strtr($_SERVER['QUERY_STRING'],'?','&'), $_GET);
}

require implode(DIRECTORY_SEPARATOR, [dirname(__DIR__, 3), 'src', '_autoload.php']);

// Fetch your code by running authorize/index.php
// export code=…
// curl -id "grant_type=authorization_code&redirect_uri=http://[::1]:1234/callback/&client_id=no.fyrkat.oauth&code=$code&code_verifier=dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk" 'http://[::1]:1080/oauth/token/'

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();
$realm = $app->getRealm( $_GET['realm'] ?? 'example.com' );
$oauth = $app->getOAuthHandler( $realm );

$oauth->handleAccessTokenPostRequest();
