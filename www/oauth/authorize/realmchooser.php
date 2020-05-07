<?php declare(strict_types=1);

$guessRealm = $app->guessRealm( $realm );
$browserAuth = $app->getBrowserAuthenticator( $realm );

if ( !( $e instanceof letswifi\browserauth\MismatchIdpException ) ) {
	header( 'Content-Type: text/plain', true, 403 );
	die( "403 Forbidden\r\n\r\nThis file cannot be accessed directly\r\n" );
}
?><!DOCTYPE html>
<html lang="en" class="dialog">
<link rel="stylesheet" href="/assets/geteduroam.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login mismatch</title>
<main>
	<p>
		Your account is not valid for <strong><?= htmlspecialchars( $realm->getName() ) ?></strong><?= '' ?>
<?php if ( null !== $guessRealm ): ?>
, but it might be valid for <strong><?= htmlspecialchars( $guessRealm->getName() ) ?></strong>.
<?php else: ?>
.
<?php endif; ?>
		You can log in with a different account, or pick a different institution.
	</p>
<?php if ( null !== $guessRealm ):
$getParams = $_GET; $getParams['realm'] = $guessRealm->getName();
$guessBrowserAuth = $app->getBrowserAuthenticator( $guessRealm );
try {
	$user = $app->getUserFromBrowserSession( $guessRealm );
?>
	<p>
		<a href="?<?= htmlspecialchars( http_build_query( $getParams ), ENT_QUOTES ) ?>" class="btn btn-default fullwidth">
			Try logging in with <strong><?= htmlspecialchars( $guessRealm->getName() ) ?></strong>
			as <strong><?= htmlspecialchars( $user->getUserID() ) ?></strong>
			instead
		</a>
	</p>
<?php
} catch ( letswifi\browserauth\MismatchIdpException $e ) {
	echo "<pre>$e</pre>";
}
endif; ?>
	<p>
		<a href="<?= htmlspecialchars( $browserAuth->getLogoutUrl(), ENT_QUOTES ) ?>" class="btn btn-default fullwidth">
			Try a different account with access to <strong><?= htmlspecialchars( $realm->getName() ) ?></strong>
		</a>
	</p>
	<p>
		<a href="<?= htmlspecialchars( $oauth->getRedirectUrlForRefusedAuthorizeRequest(), ENT_QUOTES ) ?>" class="btn btn-default fullwidth">
			Go back and try a different institution
		</a>
	</p>
</main>
