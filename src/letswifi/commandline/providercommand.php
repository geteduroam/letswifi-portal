<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\commandline;

use fyrkat\multilang\MultiLanguageString;

class ProviderCommand extends Command
{
	public const HELP = [
		'' . self::BOLD . 'list' . self::NORMAL . '',
	];

	public function run(): void
	{
		switch ( $this->argv[1] ?? '' ) {
			case 'list':
				$this->list();
				break;

			default:
				$helpCommand = new HelpCommand( [$this->argv[0], $this->getName()] );
				$helpCommand->run();

				exit( 2 );
		}
	}

	public function list(): void
	{
		$providers = $this->config->getDictionary( 'provider' );
		$table = new Table( 'http_host', 'display_name', 'contact', 'auth_service' );
		foreach ( $providers as $name => $provider ) {
			$displayName = $provider->getObject( 'display_name', MultiLanguageString::class )->jsonSerialize();
			$contact = $provider->getStringOrNull( 'contact' ) ?? '-';
			$authService = $provider->getDictionary( 'auth' )->getString( 'service' );
			$table->add( $name, \reset( $displayName )['display'], $contact, $authService );
		}

		echo $table->printTable( margin: 0, header: true );
	}
}
