<?php declare(strict_types=1);
require implode(DIRECTORY_SEPARATOR, [dirname(__DIR__, 2), 'src', '_autoload.php']);

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();

$app->render( [
		'apps' => [
				'android' => [
						'url' => 'https://geteduroam.no/app/geteduroam.apk',
						'name' => 'Android',
					],
				'ios' => [
						'url' => 'https://testflight.apple.com/join/80AujtVR',
						'name' => 'iOS',
					],
				'windows' => [
						'url' => 'https://geteduroam.no/app/geteduroam.exe',
						'name' => 'Windows',
					],
			],
	], 'apps' );
