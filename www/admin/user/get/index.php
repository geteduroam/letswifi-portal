<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2022, Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: 2020-2022, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 4 ), 'src', '_autoload.php'] );
$basePath = '../../..';

$user = $_POST['user'] ?? $_GET['user'] ?? null;
$user = \is_array( $user ) ? null : $user;
$subject = $_POST['subject'] ?? $_GET['subject'] ?? null;
$subject = \is_array( $subject ) ? null : $subject;
if ( !\is_string( $user ) && !\is_string( $subject ) ) {
	\header( 'Content-Type: text/plain', true, 400 );
	exit( "400 Bad Request\r\n\r\nMissing GET parameter user or subject\r\n" );
}

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();

$app->requireAdmin( 'admin-user-get' );

$realmManager = $app->getRealmManager();
$realm = $app->getRealm();

if ( $user ) {
	$certificates = $realmManager->listUserCertificates( $realm->getName(), $user );
	$queryVars = ['user' => $user];
} elseif ( $subject ) {
	$certificate = $realmManager->getCertificate( $realm->getName(), $subject );
	if ( null === $certificate ) {
		\header( 'Content-Type: text/plain', true, 404 );
		exit( "404 Not Found\r\n\r\nNo certificate found with subject {$subject}\r\n" );
	}
	$user = $certificate['requester'];
	$certificates = [$certificate];
	$userQueryVars = ['user' => $user];
	$queryVars = ['subject' => $subject];
} else {
	\assert( false, 'Neither user or subject provided, this should not be possible' );
	exit;
}

$app->render( [
	'href' => "{$basePath}/admin/user/get/?" . \http_build_query( $queryVars ),
	'jq' => '.certificates | map(del(.csr,.x509))',
	// TSV seems like fun, but it looks like empty columns disappear
	// 'jq' => '.certificates[] | [.serial, .requester, .sub, .issued, .expires, .revoked, .usage, .client] | @tsv',
	'certificates' => $certificates,
	'user' => ['name' => $user],
	'viewAll' => isset( $userQueryVars ) ? '?' . \http_build_query( $userQueryVars ) : null,
	'form' => [
		'realm' => $realm->getName(),
		'action' => '../../ca/revoke/',
		'revokeAll' => !$subject,
	],
], 'admin-user-get', $basePath );
