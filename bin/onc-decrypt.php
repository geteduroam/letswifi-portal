#!/usr/bin/env php
<?php
if ( 2 !== $argc ) {
	\printf( "Usage: %s pin <input.onc >output.onc\n", $argv[0] );
	exit(1);
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
	exit(2);
}
if ( \base64_decode( $parsed['HMAC'], true ) !== $hmac ) {
	echo "Invalid HMAC\n";
	exit(2);
}

if ( $data ) {
	echo "${data}\n";
}
