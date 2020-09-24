<?php declare(strict_types=1);
// Hack, https://github.com/geteduroam/ionic-app/issues/9
if (strpos($_SERVER['QUERY_STRING'], '?')) {
    parse_str(strtr($_SERVER['QUERY_STRING'],'?','&'), $_GET);
}

require implode(DIRECTORY_SEPARATOR, [dirname(__DIR__, 3), 'src', '_autoload.php']);

// The old ionic app uses GET here, so allow for now to keep compatibility
// The current ionic app does this OK, so no issue link
$invalidRequest = $_SERVER['REQUEST_METHOD'] !== 'POST';

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
if ( $grant->getClientId() === 'f817fbcc-e8f4-459e-af75-0822d86ff47a' ) {
	$payload = str_replace( '<ClientCertificate format="PKCS12" encoding="base64">', '<ClientCertificate>', $payload );
	// Allow the old app to behave badly
}
if ( in_array( $grant->getClientId(),
		[
			// List of clients that GET where they should POST
			'app.geteduroam.win',
			'f817fbcc-e8f4-459e-af75-0822d86ff47a',
		], true )
) {
	$invalidRequest = false;
}
if ( $invalidRequest ) {
	header( 'Content-Type: text/plain', true, 405 );
	die( "405 Method Not Allowed\r\n\r\nOnly POST is allowed for this resource\r\n" );
}

header( 'Content-Type: ' . $generator->getContentType() );
echo $payload;
