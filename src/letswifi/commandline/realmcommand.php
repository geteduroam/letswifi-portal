<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\commandline;

use fyrkat\configmap\ConfigurationException;
use fyrkat\multilang\MultiLanguageString;

class RealmCommand extends Command
{
	public const HELP = [
		'' . self::BOLD . 'list' . self::NORMAL . '',
		'' . self::BOLD . 'view' . self::NORMAL . ' realm',
		'' . self::BOLD . 'create' . self::NORMAL . ' realm ( --newca | --signer ) common-name --network network [args ...]',
	];

	public const LONG_HELP = [
		'List all realms',
		'View details about the requested realm',
		<<<CREATE
			Create a new realm
			--newca common-name    	Create a new CA that will be used as signer
			--signer common-name   	Use existing CA as signer
			--network network      	Name of the network that will be configured by profiles from this realm
			--name names           	Name of the realm (localisable)
			--description          	Description of the realm (localisable)
			--days days            	Integer amount of days that the credential will be valid for after issuance
			--servername servername	Name on the server certificate, to be verified by the client
			--logofile filename    	Logo filename, file must already be present in directory

			The options --network and --servername can be provided multiple times,
			all provided values will be set in the realm.

			The options --name and --description can be provided multiple times
			together with --lang, in order to localise the name and description of this realm.
			CREATE,
	];

	protected readonly ?string $realm;

	/** @var array<string> */
	protected readonly array $args;

	public function __construct( array $argv )
	{
		parent::__construct( $argv );
		$this->realm = $argv[2] ?? null;
		$args = $argv;
		\array_shift( $args );
		\array_shift( $args );
		$this->args = $args;
	}

	public function run(): void
	{
		switch ( $this->argv[1] ?? '' ) {
			case 'list':
				$this->listRealms();
				break;

			case 'view':
				$this->viewRealm();
				break;

			case 'create':
				$this->updateRealm();
				break;

			default:
				$helpCommand = new HelpCommand( [$this->argv[0], $this->getName()] );
				$helpCommand->run();

				exit( 2 );
		}
	}

	private function listRealms(): void
	{
		$realms = $this->config->getDictionary( 'realm' );
		$table = new Table( 'http_host', 'display_name', 'contact', 'validity', 'server_name', 'network' );
		foreach ( $realms as $name => $realm ) {
			$displayName = $realm->getObject( 'display_name', MultiLanguageString::class )->jsonSerialize();
			$contact = $realm->getStringOrNull( 'contact' ) ?? '-';
			$validity = $realm->getInt( 'validity' );
			$serverName = $realm->getStrings( 'server_names' )[0];
			$network = $realm->getStrings( 'network' )[0];
			$table->add( $name, \reset( $displayName )['display'], $contact, (string)$validity, $serverName, $network );
		}

		echo $table->printTable( margin: 0, header: true );
	}

	private function viewRealm(): void
	{
		\assert( null !== $this->realm );
		$realm = $this->config->getDictionary( 'realm' )->getDictionary( $this->realm );
		$table = new Table( 'setting', 'value' );
		$displayName = $realm->getObject( 'display_name', MultiLanguageString::class )->jsonSerialize();
		$table->add( 'display_name', \reset( $displayName )['display'] );
		$table->add( 'contact', $realm->getStringOrNull( 'contact' ) ?? '-' );
		$table->add( 'validity', (string)$realm->getInt( 'validity' ) );

		/** @psalm-suppress PossiblyFalseArgument */
		$table->add( 'server_name', \json_encode( $realm->getStrings( 'server_names' ) ) );

		/** @psalm-suppress PossiblyFalseArgument */
		$table->add( 'networks', \json_encode( $realm->getStrings( 'networks' ) ) );
		$table->add( 'signer', $realm->getString( 'signer' ) );

		echo $table->printTable( margin: 0, header: true )
		. "trust\n            \t- "
		. \implode( "\n            \t- ", $realm->getStrings( 'trust' ) )
		. "\n";
	}

	private function updateRealm(): void
	{
		\assert( null !== $this->realm );
		$realmConfig = $this->config->getDictionary( 'realm' );
		$certificateConfig = $this->config->getDictionary( 'certificate' );
		$realm = $this->createConfig( $this->realm, $this->args );

		try {
			$realmConfig[$this->realm] = $realm;
		} catch ( ConfigurationException $e ) {
			static::die( 2, $e->getMessage() );
		}
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
					$newCA = $args[$i] ?? static::die( 2, '--newca expects a name for the new CA' );
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
				case '--networks':
				case '--network':
					$i++;
					$result['networks'] ??= [];
					$result['networks'][] = $args[$i];
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
			if ( !\array_key_exists( 'contact', $result ) ) {
				$result['contact'] = null;
			}
		}
		if ( null === $newCA && !\array_key_exists( 'signer', $result ) ) {
			static::die( 2, 'Must provide either --signer or --newca' );
		}
		if ( null !== $newCA && \array_key_exists( 'signer', $result ) ) {
			static::die( 2, 'Cannot provide --signer when also creating a new CA;', 'the new CA will be the signer.' );
		}
		if ( null !== $newCA ) {
			$result['signer'] = $this->createSigningCertificate( $newCA );
			if ( empty( $result['trust'] ?? [] ) ) {
				$result['trust'] = [$result['signer']];
			}
		}
		if ( empty( $result['trust'] ?? [] ) ) {
			static::die( 2, 'Must provide at least one trusted CA' );
		}
		if ( empty( $result['networks'] ?? [] ) ) {
			static::die( 2, 'Must provide at least one network' );
		}

		return $result;
	}
}
