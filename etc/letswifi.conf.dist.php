<?php return [
	'pdo' => [
		'dsn' => 'sqlite:' . dirname( __DIR__ ) . '/var/letswifi-dev.sqlite',
		'username' => null,
		'password' => null,
	],
] + require __DIR__ . '/provider.php';
