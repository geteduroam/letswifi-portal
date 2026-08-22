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
	public const HELP = [
		'' . self::BOLD . 'decrypt' . self::NORMAL . ' <pin>',
	];

	public const LONG_HELP = [<<<EOT
		Read an encrypted ONC file from stdin,
		decrypt it using <pin> and write the unencrypted ONC to stdout.
		EOT];

	public function run(): void
	{
		switch ( $this->argv[1] ?? '' ) {
			case 'decrypt':
				$this->decrypt();
				break;

			default:
				$helpCommand = new HelpCommand( [$this->argv[0], $this->getName()] );
				$helpCommand->run();

				exit( 2 );
		}
	}

	public function decrypt(): void
	{
		$password = $this->argv[1] ?? self::die( 2, 'No PIN provided' );
		$input = \file_get_contents( 'php://stdin' ) ?: self::die( 2, 'Malformed ONC' );
		$parsed = \json_decode( $input, true );
		$salt = \base64_decode( $parsed['Salt'], true ) ?: self::die( 2, 'Malformed ONC' );
		$initVector = \base64_decode( $parsed['IV'], true ) ?: self::die( 2, 'Malformed ONC' );

		$encryptionKey = \hash_pbkdf2( 'sha1', $password, $salt, $parsed['Iterations'], 32, true );
		$data = \openssl_decrypt(
			\base64_decode( $parsed['Ciphertext'], true ) ?: self::die( 2, 'Malformed ONC' ),
			'AES-256-CBC',
			$encryptionKey,
			\OPENSSL_RAW_DATA,
			$initVector,
		);
		$hmac = \hash_hmac(
			'sha1',
			\base64_decode( $parsed['Ciphertext'], true ) ?: self::die( 2, 'Malformed ONC' ),
			$encryptionKey,
			true,
		);

		if ( 'SHA1' !== $parsed['HMACMethod'] ) {
			self::die( 2, 'Invalid HMAC algo' );
		}
		if ( \base64_decode( $parsed['HMAC'], true ) !== $hmac ) {
			self::die( 2, 'Invalid HMAC' );
		}

		if ( false === $data ) {
			self::die( 2, 'ONC Decrypt failed' );
		}

		echo "{$data}\n";
	}
}
