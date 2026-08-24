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

	public const LONG_HELP = [
		'Create a new certificate with private key, with the provided common name.',
		'Import an existing certificate and (optionally) private key, read from stdin.',
	];

	public function run(): void
	{
		switch ( $this->argv[1] ?? '' ) {
			case 'import':
				$this->importCertificates();
				break;
			case 'create':
				if ( $this->argv[2] ?? '' ) {
					static::die( 2, 'No common-name provided' );
				}
				$this->createSigningCertificate( $this->argv[2] );
				break;

			default:
				$helpCommand = new HelpCommand( [$this->argv[0], $this->getName()] );
				$helpCommand->run();

				exit( 2 );
		}
	}

	protected function importCertificates(): void
	{
		$certificateConfig = $this->config->getDictionary( 'certificate' );
		$stdin = \file_get_contents( 'php://stdin' );
		\preg_match_all( '/(?:^|\\R)-----BEGIN(?: (EC|RSA))? PRIVATE KEY-----\\R.*?\\R-----END(?: \1)? PRIVATE KEY-----(?:$|\\R)/sm', $stdin, $keys );
		\preg_match_all( '/(?:^|\\R)-----BEGIN CERTIFICATE-----\\R.*?\\R-----END CERTIFICATE-----(?:$|\\R)/sm', $stdin, $certificates );

		$keys = \array_map( static fn( string $key ) => new PrivateKey( $key ), $keys[0] );
		$certificates = \array_map( static fn( string $certificate ) => new X509( $certificate ), $certificates[0] );

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
