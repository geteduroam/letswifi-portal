<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi;

use DomainException;
use PDO;

class Config
{
	/** @var array<string,mixed> */
	private $conf;

	/** @var ?PDO */
	private $pdo;

	/**
	 * @param ?array|string $conf PHP file
	 */
	public function __construct( $conf = null )
	{
		if ( null === $conf ) {
			$conf = \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 2 ), 'etc', 'letswifi.conf.php'] );
		}
		if ( \is_string( $conf ) ) {
			if ( !\file_exists( $conf ) ) {
				throw new DomainException( 'Configuration file missing: ' . $conf );
			}
			$conf = require $conf;
		}
		if ( !\is_array( $conf ) ) {
			throw new DomainException( 'Configuration should be array' );
		}
		$this->conf = $conf;
	}

	public function getString( string $key ): string
	{
		if ( !\array_key_exists( $key, $this->conf ) ) {
			throw new DomainException( "Expecting config key {$key} to be string, but does not exist" );
		}
		$data = $this->conf[$key];
		if ( !\is_string( $data ) ) {
			throw new DomainException( "Expecting config key {$key} to be string, but is " . \gettype( $data ) );
		}

		return $data;
	}

	public function getArray( string $key ): array
	{
		if ( !\array_key_exists( $key, $this->conf ) ) {
			throw new DomainException( "Expecting config key {$key} to be string, but does not exist" );
		}
		$data = $this->conf[$key];
		if ( !\is_array( $data ) ) {
			throw new DomainException( "Expecting config key {$key} to be string, but is " . \gettype( $data ) );
		}

		return $data;
	}

	public function getArrayOrEmpty( string $key ): array
	{
		return $this->getArrayOrNull( $key ) ?? [];
	}

	public function getArrayOrNull( string $key ): ?array
	{
		if ( !isset( $this->conf[$key] ) ) {
			return null;
		}
		$data = $this->conf[$key];
		if ( !\is_array( $data ) ) {
			throw new DomainException( "Expecting config key {$key} to be string, but is " . \gettype( $data ) );
		}

		return $data;
	}

	public function getStringOrNull( string $key ): ?string
	{
		if ( !isset( $this->conf[$key] ) ) {
			return null;
		}
		$data = $this->conf[$key];
		if ( !\is_string( $data ) ) {
			throw new DomainException( "Expecting config key {$key} to be string, but is " . \gettype( $data ) );
		}

		return $data;
	}
}
