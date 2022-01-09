<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2021, Jørn Åne de Jong, Uninett AS <jornane.dejong@surf.nl>
 * Copyright: 2020-2021, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

require \implode(\DIRECTORY_SEPARATOR, [\dirname(__DIR__, 4), 'src', '_autoload.php']);

$user = $_POST['user'] ?? $_GET['user'];
if ( !\is_string( $user ) ) {
	\header( 'Content-Type: text/plain', true, 400 );
	exit( "400 Bad Request\r\n\r\nMissing GET parameter user\r\n" );
}

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();

$app->requireAdmin( 'admin-user-get' );

$realmManager = $app->getRealmManager();
$realm = $app->getRealm();

$certificates = $realmManager->listUserCertificates( $realm->getName(), $user );

$app->render( [
	'href' => '/admin/user/get/?' . \http_build_query( ['user' => $user] ),
	'jq' => '.certificates | map(del(.csr,.x509))',
	// TSV seems like fun, but it looks like empty columns disappear
	//'jq' => '.certificates[] | [.serial, .requester, .sub, .issued, .expires, .revoked, .usage, .client] | @tsv',
	'certificates' => $certificates,
	'user' => ['name' => $user],
	'form' => [
		'realm' => $realm->getName(),
		'action' => '../../ca/revoke/',
	],
], 'admin-user-get' );
