<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\commandline;

use Exception;
use fyrkat\openssl\PrivateKey;
use fyrkat\openssl\X509;

class CACommand extends Command
{
	public const HELP = [
		'' . self::BOLD . 'create' . self::NORMAL . ' <common-name>',
		'' . self::BOLD . 'import' . self::NORMAL . '',
	];

	public function run(): void
	{
		$arg1 = \array_key_exists( 1, $this->argv ) ? $this->argv[1] : '';
		$arg2 = \array_key_exists( 2, $this->argv ) ? $this->argv[2] : null;

		switch ( $arg1 ) {
			case 'import':
				$this->importCertificates();
				break;
			case 'create':
				if ( null === $arg2 ) {
					static::print_error( 'No common-name provided ' );

					exit( 2 );
				}
				$this->createSigningCertificate( $arg2 );
				break;

			default:
				static::print_error( 'Unknown command: ' . $arg1 );

				exit( 2 );
		}
	}

	protected function importCertificates(): void
	{
		$certificateConfig = $this->config->getDictionary( 'certificate' );
		$stdin = \file_get_contents( 'php://stdin' );
		\preg_match_all( '/(^|\\n)-----BEGIN( EC| RSA)? PRIVATE KEY-----\\n.*?\\n-----END\\2 PRIVATE KEY-----($|\\n)/sm', $stdin, $keys );
		\preg_match_all( '/(^|\\n)-----BEGIN CERTIFICATE-----\\n.*?\\n-----END CERTIFICATE-----($|\\n)/sm', $stdin, $certificates );

		$keys = \array_map( static fn ( string $key ) => new PrivateKey( $key ), $keys[0] );
		$certificates = \array_map( static fn ( string $certificate ) => new X509( $certificate ), $certificates[0] );

		for ( $i = \count( $certificates ) - 1; 0 <= $i; --$i ) {
			$x509 = $certificates[$i];
			$sub = (string)$x509->getSubject();
			if ( $certificateConfig->has( $sub ) ) {
				static::print_error( "Skipping {$sub} (already imported)" );
				continue;
			}
			$key = null;
			foreach ( $keys as $candidateKey ) {
				if ( $x509->checkPrivateKey( $candidateKey ) ) {
					$key = $candidateKey;
					break;
				}
			}
			$msg = 'Importing';
			if ( null !== $key ) {
				$msg .= ' with key';
			}
			$msg .= ':';
			static::print_error( $msg );
			static::print_error( 'i: ' . $x509->getIssuerSubject() );
			static::print_error( 's: ' . $sub );

			try {
				$this->importCA( $x509, $key );
			} catch ( Exception $e ) {
				static::print_error( 'ERR: ' . $e->getMessage() );
			}
		}
	}
}
