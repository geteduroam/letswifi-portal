#!/usr/bin/env php
<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2022, Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: 2020-2022, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

if ( 2 !== $argc ) {
	\printf( "Usage: %s pin <input.onc >output.onc\n", $argv[0] );
	exit( 1 );
}

$input = \file_get_contents( 'php://stdin' );
$parsed = \json_decode( $input, true );
$salt = \base64_decode( $parsed['Salt'], true );
$initVector = \base64_decode( $parsed['IV'], true );

$password = $argv[1];
$encryptionKey = \hash_pbkdf2( 'sha1', $password, $salt, $parsed['Iterations'], 32, true );
$data = \openssl_decrypt( \base64_decode( $parsed['Ciphertext'], true ), 'AES-256-CBC', $encryptionKey, \OPENSSL_RAW_DATA, $initVector );
$hmac = \hash_hmac( 'sha1', \base64_decode( $parsed['Ciphertext'], true ), $encryptionKey, true );

if ( 'SHA1' !== $parsed['HMACMethod'] ) {
	echo "Invalid HMAC algo\n";
	exit( 2 );
}
if ( \base64_decode( $parsed['HMAC'], true ) !== $hmac ) {
	echo "Invalid HMAC\n";
	exit( 2 );
}

if ( false === $data ) {
	echo "Decrypt failed\n";
	exit( 2 );
}

echo "{$data}\n";
