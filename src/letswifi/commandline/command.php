<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\commandline;

use Throwable;
use fyrkat\openssl\CSR;
use fyrkat\openssl\DN;
use fyrkat\openssl\OpenSSLConfig;
use fyrkat\openssl\OpenSSLKey;
use fyrkat\openssl\PrivateKey;
use fyrkat\openssl\X509;
use letswifi\configuration\DictionaryFile;
use letswifi\configuration\DictionaryPemDir;

class Command
{
	/** @var array<string,class-string<Command>> */
	public const COMMANDS = [
		'help' => HelpCommand::class,
		'provider' => ProviderCommand::class,
		'realm' => RealmCommand::class,
		'database' => DatabaseCommand::class,
		'ca' => CACommand::class,
		'sign' => SignCommand::class,
		'onc' => ONCCommand::class,
	];

	public const BOLD = "\033[1m";

	public const NORMAL = "\033[0m";

	/** @var array<string> */
	public const HELP = [];

	/** @var non-empty-array<int,string> */
	public readonly array $argv;

	protected readonly DictionaryFile $config;

	/**
	 * @param non-empty-array<string> $argv
	 */
	public function __construct( array $argv )
	{
		if ( '/' === $argv[0][0] ) {
			$argv[0] = \basename( $argv[0] );
		}
		$this->argv = \array_values( $argv );
		$this->config = new DictionaryFile( \dirname( __DIR__, 3 ) . '/config/letswifi.conf.php' );
	}

	public function run(): void
	{
		\assert( self::class === static::class );
		$argv = $this->argv;
		$arg0 = \array_shift( $argv );
		$arg1 = \array_shift( $argv ) ?? 'help';
		if ( !\array_key_exists( $arg1, self::COMMANDS ) ) {
			static::print_error( "Unknown command: {$arg1}" );
			$arg1 = 'help';
		}
		$class = self::COMMANDS[$arg1];
		\array_unshift( $argv, 'help' === $arg1 ? $arg0 : "{$arg0}-{$arg1}" );

		try {
			$command = new $class( $argv );
		} catch ( Throwable $e ) {
			static::print_error( $e->getMessage() );

			return;
		}
		$command->run();
	}

	protected static function print_error( string ...$s ): void
	{
		\fwrite( \STDERR, \implode( \PHP_EOL, $s ) . \PHP_EOL );
	}

	protected function createSigningCertificate( string $commonName ): string
	{
		$caPrivKey = new PrivateKey( new OpenSSLConfig( privateKeyType: OpenSSLKey::KEYTYPE_EC ) );
		$caCsr = CSR::generate(
			new DN( ['CN' => $commonName] ), // Subject
			$caPrivKey, // CA key
		);
		$caCertificate = $caCsr->sign(
			null, // CA certificate
			$caPrivKey, // CA key
			18250, // Validity in days
			new OpenSSLConfig( x509Extensions: OpenSSLConfig::X509_EXTENSION_CA ), // EKU
		);

		return static::importCA( $caCertificate, $caPrivKey );
	}

	protected function importCA( X509 $x509, ?PrivateKey $key ): string
	{
		$certificateConfig = $this->config->getDictionary( 'certificate' );
		$certificateDir = $certificateConfig instanceof DictionaryPemDir ? $certificateConfig->dir : null;
		if ( null === $certificateDir ) {
			static::print_error( 'Can only write certificates if certificate#dir is used in the config file' );

			exit( 2 );
		}
		$subject = $x509->getSubject( longNames: false )->__toString();
		if ( \str_contains( $subject, '/' ) || \str_contains( $subject, '\\' ) ) {
			\var_export( \str_contains( $subject, '/' ) );
			\var_export( \str_contains( $subject, '\\' ) );
			static::print_error( 'Certificate subject contains invalid sequences' );

			exit( 2 );
		}
		$filename = $certificateDir . \DIRECTORY_SEPARATOR . "{$subject}.pem";
		$privKeyPem = \trim( $key?->getPrivateKeyPem( null ) ?? '' );
		if ( !empty( $privKeyPem ) ) {
			$privKeyPem .= "\n";
		}
		\file_put_contents( $filename, \implode( "\n", [
			\trim( $x509->getX509Pem() ),
			$privKeyPem,
		] ) );

		return $subject;
	}

	/**
	 * @param null|array|scalar $v
	 */
	protected function export( mixed $v, int $indent = 0 ): string
	{
		if ( \is_array( $v ) ) {
			return $this->exportArray( $v, $indent );
		}
		if ( null === $v ) {
			return 'null';
		}
		if ( true === $v ) {
			return 'true';
		}
		if ( false === $v ) {
			return 'false';
		}

		return \var_export( $v, true );
	}

	protected function exportArray( array $a, int $indent = 0 ): string
	{
		switch ( \count( $a ) ) {
			case 0:
				return '[]';
			case 1:
				if ( 0 === \key( $a ) ) {
					return '[' . $this->export( \current( $a ), $indent + 1 ) . ']';
				}
		}
		$in = \str_repeat( "\t", $indent );
		$showKeys = false;
		$count = 0;
		foreach ( $a as $k => $_ ) {
			if ( $k !== $count++ ) {
				$showKeys = true;
			}
		}
		$output = '[' . \PHP_EOL;
		foreach ( $a as $k => $v ) {
			$output .= "{$in}\t"
				. ( $showKeys ? $this->export( $k, $indent + 1 ) . ' => ' : '' )
				. $this->export( $v, $indent + 1 )
				. ',' . \PHP_EOL;
		}

		return "{$output}{$in}]";
	}
}
if ( \PHP_SAPI !== 'cli' ) {
	\fwrite( \STDERR, 'This program is intended to be run from the command line.' . \PHP_EOL );

	exit( 1 );
}
