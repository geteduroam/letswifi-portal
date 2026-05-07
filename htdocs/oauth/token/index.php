<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

use letswifi\LetsWifiApp;

require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 3 ), 'autoload.php'] );

// Fetch your code by running authorize/index.php
// export code=…
// curl -id "grant_type=authorization_code&redirect_uri=http://[::1]:1234/callback/&client_id=no.fyrkat.oauth&code=$code&code_verifier=dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk" 'http://[::1]:1080/oauth/token/'

$app = new LetsWifiApp( urlRelativeBase: '../..' );
$provider = $app->getProvider();
$oauth = $provider->auth->oauth;

$oauth->handleTokenPostRequest();
