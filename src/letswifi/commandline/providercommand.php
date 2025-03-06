<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\commandline;

class ProviderCommand extends Command
{
	public const HELP = [''];

	public function run(): void
	{
		$providers = $this->config->getDictionaryList( 'provider' );
		echo "HTTP HOST\tDISPLAY NAME\tCONTACT\tAUTH SERVICE" . \PHP_EOL;
		foreach ( $providers as $name => $provider ) {
			$displayName = $provider->getMultiLanguageString( 'display_name' )->jsonSerialize();
			$contact = $provider->getStringOrNull( 'contact' ) ?? '-';
			$authService = $provider->getDictionary( 'auth' )->getString( 'service' );
			echo "{$name}\t" . \reset( $displayName )['display'] . "\t{$contact}\t{$authService}" . \PHP_EOL;
		}
	}
}
