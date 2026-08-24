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
use UnexpectedValueException;
use fyrkat\configmap\ConfigurationException;
use fyrkat\configmap\DictionaryPhpFile;
use fyrkat\openssl\CSR;
use fyrkat\openssl\DN;
use fyrkat\openssl\OpenSSLConfig;
use fyrkat\openssl\OpenSSLKey;
use fyrkat\openssl\PrivateKey;
use fyrkat\openssl\X509;
use letswifi\configmap\DictionaryPemDir;
use letswifi\configmap\DictionaryPemFile;

class Command
{
	/** @var array<string,class-string<Command>> */
	public const COMMANDS = [
		'help' => HelpCommand::class,
		'provider' => ProviderCommand::class,
		'realm' => RealmCommand::class,
		'ca' => CACommand::class,
		'onc' => ONCCommand::class,
	];

	public const BOLD = "\033[1m";

	public const NORMAL = "\033[0m";

	/** @var array<int,string> */
	public const HELP = [];

	/** @var array<int,string> */
	public const LONG_HELP = [];

	/** @var non-empty-array<int,string> */
	public readonly array $argv;

	protected readonly DictionaryPhpFile $config;

	/**
	 * @param non-empty-array<string> $argv
	 */
	public function __construct( array $argv )
	{
		if ( '/' === $argv[0][0] ) {
			$argv[0] = \basename( $argv[0] );
		}
		$this->argv = \array_values( $argv );

		$this->config = new DictionaryPhpFile( 'letswifi.conf.php', [
			\dirname( __DIR__, 3 ) . \DIRECTORY_SEPARATOR . 'config',
			\dirname( __DIR__, 3 ) . \DIRECTORY_SEPARATOR . 'defaults',
		], sigils: [
			...DictionaryPhpFile::sigils(),
			...DictionaryPemFile::sigils(),
		] );
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

	protected static function die( int $status, string ...$s ): never
	{
		static::print_error( ...$s );

		exit( $status );
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

		if ( !$certificateConfig instanceof DictionaryPemDir ) {
			static::die( 2, 'Can only write certificates if certificate#pemdir is used in the config file' );
		}

		try {
			return $certificateConfig->importCertificate( $x509, $key );
		} catch ( ConfigurationException $e ) {
			static::die( 2, $e->getMessage() );
		}
	}

	protected function getName(): string
	{
		$classSegments = \explode( '\\', static::class );
		$class = \end( $classSegments );
		if ( \str_ends_with( $class, 'Command' ) ) {
			return \strtolower( \substr( $class, 0, -7 ) );
		}

		throw new UnexpectedValueException( 'Cannot find command name from class name ' . $class );
	}
}

if ( \PHP_SAPI !== 'cli' ) {
	\fwrite( \STDERR, 'This program is intended to be run from the command line.' . \PHP_EOL );

	exit( 1 );
}
