<?php declare(strict_types=1);
require implode(DIRECTORY_SEPARATOR, [dirname(__DIR__, 2), 'src', '_autoload.php']);

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();

$app->render( [
		'href' => '/dev/',
	], 'dev' );
