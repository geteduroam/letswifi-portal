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
<link rel="stylesheet" href="/assets/geteduroam.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Authorize</title>
<form method="post">
<header>
	<p class="logout">
		<button type="submit" name="approve" value="no" class="btn btn-txt">Not <?= htmlspecialchars( $user->getUserID() ); ?>?</button>
	</p>
</header>
<main>
	<p>Do you want to use your account to connect to eduroam on this device?</p>
	<p class="text-center"><button type="submit" class="btn btn-default" name="approve" value="yes">Approve</button></p>
	<hr>
	<details>
		<summary>Why is this needed?</summary>
		<p>By clicking approve, you allow the application to receive Wi-Fi profiles on your behalf.</p>
	</details>
</main>
</form>
