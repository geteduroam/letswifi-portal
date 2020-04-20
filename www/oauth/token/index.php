<?php declare(strict_types=1);
require implode(DIRECTORY_SEPARATOR, [dirname(__DIR__, 3), 'src', '_autoload.php']);

// Fetch your code by running authorize/index.php
// export code=…
// curl -id "grant_type=authorization_code&redirect_uri=http://[::1]:1234/callback/&client_id=no.fyrkat.oauth&code=$code&code_verifier=dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk" 'http://[::1]:1080/oauth/token/'

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();
$realm = $app->getRealm();
$oauth = $app->getOAuthHandler( $realm );

$oauth->handleAccessTokenPostRequest();
