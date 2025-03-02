<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\configuration;

class DictionaryPemDir extends DictionaryDir
{
	/** @var string */
	protected const SIGIL = '#pem';

	/** @var string */
	protected const EXTENSION = 'pem';

	public function __construct( string $dir, ?string $extension = null )
	{
		parent::__construct( $dir, $extension );
		if ( \fileperms( $dir ) & 0o07 ) {
			throw new ConfigurationException( "Directory {$dir} has too wide permissions" );
		}
	}
}
