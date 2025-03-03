<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\configuration;

use DomainException;

class DictionaryDir extends DictionaryFile
{
	/** @var string */
	protected const SIGIL = '#inc';

	/** @var string */
	protected const EXTENSION = 'conf.php';

	protected readonly string $extension;

	public function __construct( string $dir, ?string $extension = null )
	{
		$this->extension = $extension ?? static::EXTENSION;
		$this->dir = $dir;
		$this->baseDir = $dir;
		$conf = static::createList( $dir, $this->extension );
		Dictionary::__construct( $conf );
		$this->data = $conf; // Psalm doesn't seem to understand that the constructor already does this
	}

	public static function createList( string $dir, ?string $extension = null, ?string $sigil = null ): array
	{
		$extension ??= static::EXTENSION;
		$sigil ??= static::SIGIL;
		if ( !$handle = \opendir( $dir ) ) {
			throw new DomainException( 'Cannot open configuration directory: ' . $dir );
		}
		$extensionLength = \strlen( $extension ) + 1;
		$conf = [];
		while ( false !== ( $entry = \readdir( $handle ) ) ) {
			if ( !$entry || '.' === $entry[0] || \substr( $entry, -1 * $extensionLength ) !== ".{$extension}" ) {
				continue;
			}
			$key = \substr( $entry, 0, -1 * $extensionLength );
			$conf[$key . static::SIGIL] = $entry;
		}

		return $conf;
	}
}
