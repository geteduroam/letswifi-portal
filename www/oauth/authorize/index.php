<?php declare(strict_types=1);
require implode(DIRECTORY_SEPARATOR, [dirname(__DIR__, 3), 'src', '_autoload.php']);

// Test this file by serving it on http://[::1]:1080/oauth/authorize/ and point your browser to:
// http://[::1]:1080/oauth/authorize/?response_type=code&code_challenge_method=S256&scope=testscope&code_challenge=E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM&redirect_uri=http://[::1]:1234/callback/&client_id=no.fyrkat.oauth&state=0

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();
$realm = $app->getRealm();
$oauth = $app->getOAuthHandler( $realm );

$oauth->assertAuthorizeRequest();

$user = $app->getUserFromBrowserSession();

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
	// This is how it should be done for production:
	$oauth->handleAuthorizePostRequest( $user->getUserID(), $_POST['approve'] === 'yes' );
	// function either throws exception or dies, but does not return
	header( 'Content-Type: text/plain' );
	die( "500 Internal Server Error\r\n\r\nRequest was not handled\r\n" );
}
?><!DOCTYPE html>
<html lang="en">
<title>Authorize</title>
<form method="post">
	<button type="submit" name="approve" value="yes">Approve</button>
	<button type="submit" name="approve" value="no">Reject</button>
</form>
