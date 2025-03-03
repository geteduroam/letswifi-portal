<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\commandline;

/**
 * @see https://chromium.googlesource.com/chromium/src/+/main/components/onc/docs/onc_spec.md#encrypted-format-example
 */
class ONCCommand extends Command
{
	public const HELP = ['pin'];

	public function run(): void
	{
		if ( !\array_key_exists( 1, $this->argv ) ) {
			self::print_error( 'No PIN provided' );

			exit( 2 );
		}
		$password = $this->argv[1];
		$input = \file_get_contents( 'php://stdin' );
		$parsed = \json_decode( $input, true );
		$salt = \base64_decode( $parsed['Salt'], true );
		$initVector = \base64_decode( $parsed['IV'], true );

		$encryptionKey = \hash_pbkdf2( 'sha1', $password, $salt, $parsed['Iterations'], 32, true );
		$data = \openssl_decrypt( \base64_decode( $parsed['Ciphertext'], true ), 'AES-256-CBC', $encryptionKey, \OPENSSL_RAW_DATA, $initVector );
		$hmac = \hash_hmac( 'sha1', \base64_decode( $parsed['Ciphertext'], true ), $encryptionKey, true );

		if ( 'SHA1' !== $parsed['HMACMethod'] ) {
			self::print_error( 'Invalid HMAC algo' );

			exit( 2 );
		}
		if ( \base64_decode( $parsed['HMAC'], true ) !== $hmac ) {
			self::print_error( 'Invalid HMAC' );

			exit( 2 );
		}

		if ( false === $data ) {
			self::print_error( 'Decrypt failed' );

			exit( 2 );
		}

		echo "{$data}\n";
	}
}
