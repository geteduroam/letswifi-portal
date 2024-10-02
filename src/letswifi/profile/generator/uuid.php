<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\profile\generator;

use Exception;

class UUID
{
	/** @var string Bytestring */
	private $bytes;

	/**
	 * Generate a new UUID.
	 *
	 * @param ?string $uuid Bytestring or UUID formatted string
	 */
	public function __construct( $uuid = null )
	{
		if ( null === $uuid ) {
			// Generate 16 bytes of random data (128 bits )
			$bytes = \random_bytes( 16 );
		} elseif ( 16 === \strlen( $uuid ) ) {
			$bytes = $uuid;
		} elseif ( \preg_match( '/[0-9a-f]{8}(\\-[0-9a-f]{4}){2}\\-[0-9a-f]{12}/', $uuid ) ) {
			$bytes = \hex2bin( \str_replace( '-', '', $uuid ) );
		} else {
			throw new Exception( 'Invalid UUID' );
		}

		// Set bits required for a valid UUIDv4
		$bytes[8] = \chr( ( \ord( $bytes[8] ) & 0x3F ) | 0x80 ); // Eat 2 bits of entropy
		$bytes[6] = \chr( ( \ord( $bytes[6] ) & 0x4F ) | 0x40 ); // Eat 4 bits of entropy

		$this->bytes = $bytes;
	}

	/**
	 * Return a string representation of the UUID.
	 *
	 * @return string UUID-formatted string (hexadecimal with dashes )
	 */
	public function __toString()
	{
		// Convert bytes to hex and split in 4-char strings (hex, so 2 bytes per string )
		$parts = \str_split( \bin2hex( $this->bytes ), 4 );

		// Add dashes where UUIDs should have dashes
		return \implode(
			'-',
			[
				$parts[0] . $parts[1],
				$parts[2],
				$parts[3],
				$parts[4],
				$parts[5] . $parts[6] . $parts[7],
			],
		);
	}

	/**
	 * Get the bytes of this UUID.
	 *
	 * @return string byte string
	 */
	public function getBytes()
	{
		return $this->bytes;
	}
}
