<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\commandline;

use letswifi\configuration\DictionaryDir;
use letswifi\configuration\DictionaryPemDir;

class RealmCommand extends Command
{
	public const HELP = [
		'', '<realm> [--newca <common-name>] [args ...]',
	];

	protected readonly ?string $realm;

	/** @var array<string> */
	protected readonly array $args;

	public function __construct( array $argv )
	{
		parent::__construct( $argv );
		$this->realm = $argv[1] ?? null;
		$args = $argv;
		\array_shift( $args );
		\array_shift( $args );
		$this->args = $args;
	}

	public function run(): void
	{
		switch ( $this->realm ) {
			case null:
				$this->listRealms();
				break;

			default:
				$this->args ? $this->updateRealm() : $this->viewRealm();
				break;
		}
	}

	private function listRealms(): void
	{
		$realms = $this->config->getDictionaryList( 'realm' );
		echo "HTTP HOST\tDISPLAY NAME\tCONTACT\tNETWORK\tVALIDITY\tSERVER NAME" . \PHP_EOL;
		foreach ( $realms as $name => $realm ) {
			$displayName = $realm->getMultiLanguageString( 'display_name' )->jsonSerialize();
			$contact = $realm->getStringOrNull( 'contact' ) ?? '-';
			$network = $realm->getRawArray( 'networks' )[0];
			$validity = $realm->getInteger( 'validity' );
			$serverName = $realm->getRawArray( 'server_names' )[0];
			echo "{$name}\t" . \reset( $displayName )['display'] . "\t{$contact}\t{$network}\t{$validity}\t{$serverName}" . \PHP_EOL;
		}
	}

	private function viewRealm(): void
	{
		\assert( null !== $this->realm );
		$realm = $this->config->getDictionary( 'realm' )->getDictionary( $this->realm );
		\var_export( $realm );
	}

	private function updateRealm(): void
	{
		\assert( null !== $this->realm );
		$realmConfig = $this->config->getDictionary( 'realm' );
		$certificateConfig = $this->config->getDictionary( 'certificate' );
		$realmDir = $realmConfig instanceof DictionaryDir ? $realmConfig->dir : null;
		$certificateDir = $certificateConfig instanceof DictionaryPemDir ? $certificateConfig->dir : null;
		if ( null === $realmDir || null === $certificateDir ) {
			static::print_error( 'Can only write realms if realm#dir and certificate#dir is used in the config file' );

			exit( 2 );
		}
		$config = $this->createConfig( $this->realm, $this->args );
		$filename = $realmDir . \DIRECTORY_SEPARATOR . "{$this->realm}.conf.php";
		\file_put_contents( $filename, '<?php return ' . $this->export( $config ) . ";\n" );
	}

	private function createConfig( string $realmId, array $args ): array
	{
		$result = [];
		$newCA = null;
		$lang = 'en-GB';
		for ( $i = 0; \count( $args ) > $i; ++$i ) {
			switch ( \strtolower( $args[$i] ) ) {
				case '--newca':
				case '--new-ca':
					$i++;
					$newCA = $args[$i];
					break;
				case '--name':
				case '--displayname':
				case '--display-name':
				case '-n':
					$i++;
					$result['display_name'][$lang] = $args[$i];
					break;
				case '--description':
					$i++;
					$result['description'][$lang] = $args[$i];
					break;
				case '--validity':
				case '--days':
					$i++;
					$result['validity'] = (int)$args[$i];
					break;
				case '--trust':
					$i++;
					$result['trust'] ??= [];
					$result['trust'][] = $args[$i];
					break;
				case '--server-names':
				case '--servernames':
				case '--server-name':
				case '--servername':
					$i++;
					$result['server_names'] ??= [];
					$result['server_names'][] = $args[$i];
					break;
				case '--signer':
					$i++;
					$result['signer'] = $args[$i];
					break;
				case '--logofile':
					$i++;
					$result['logo']['data#file'] = $args[$i];
					break;
				case '--lang':
					$i++;
					$lang = $args[$i];
					break;
			}
		}
		if ( !empty( $result ) ) {
			// Add default settings
			if ( !\array_key_exists( 'display_name', $result ) ) {
				$result['display_name'] = [$lang => $realmId];
			}
			if ( !\array_key_exists( 'description', $result ) ) {
				$result['description'] = null;
			}
			if ( !\array_key_exists( 'server_names', $result ) ) {
				$result['server_names'] = ["radius.{$realmId}"];
			}
			if ( !\array_key_exists( 'validity', $result ) ) {
				$result['validity'] = 365;
			}
			if ( !\array_key_exists( 'networks', $result ) ) {
				$result['networks'] = ['eduroam'];
			}
			if ( !\array_key_exists( 'contact', $result ) ) {
				$result['contact'] = null;
			}
		}
		if ( null !== $newCA && \array_key_exists( 'signer', $result ) ) {
			static::print_error( 'Cannot provide --signer when also creating a new CA;', 'the new CA will be the signer.' );

			exit( 2 );
		}
		if ( null !== $newCA ) {
			$result['signer'] = $this->createSigningCertificate( $newCA );
			if ( !\array_key_exists( 'trust', $result ) ) {
				$result['trust'] = [$result['signer']];
			}
		}

		return $result;
	}
}
