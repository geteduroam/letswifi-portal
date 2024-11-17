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
use OutOfBoundsException;

abstract class Config
{
	public const KEY_SEPARATOR = '->';

	protected string $keyPrefixStr = '';

	/** @var array<string> */
	protected array $keyPrefix = [];

	/** @var array<string,mixed> */
	private $conf;

	/**
	 * @param ?array|string $conf PHP file
	 */
	final public function __construct( $conf = null )
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

	/** Prevent leaking data via var_dump*/
	public function __debugInfo(): array
	{
		return [];
	}

	public function getString( string $key ): string
	{
		$data = $this->getStringOrNull( $key );

		return null === $data ? throw new DomainException( "Expecting config key {$this->keyPrefixStr}{$key} to be string, but is null" ) : $data;
	}

	public function getParentKey(): string
	{
		$keyElements = \explode( self::KEY_SEPARATOR, $this->keyPrefixStr );
		while ( \count( $keyElements ) > 0 ) {
			if ( $candidate = \array_pop( $keyElements ) ) {
				return $candidate;
			}
		}

		throw new OutOfBoundsException( 'This object is the root configuration node' );
	}

	public function getDictionaryOrNull( string $key ): ?static
	{
		$result = $this->getDictionary( $key );

		return empty( $result->conf ) ? null : $result;
	}

	public function getRawArray( string $key ): array
	{
		$data = $this->getField( $key );
		if ( !\is_array( $data ) ) {
			throw new DomainException( "Expecting config key {$this->keyPrefixStr}{$key} to be dictionary, but is " . \gettype( $data ) );
		}

		return $data;
	}

	public function getDictionary( string $key ): static
	{
		$data = $this->getField( $key );
		if ( !empty( $data ) && !\is_string( \key( $data ) ) ) {
			throw new DomainException( "Expecting config key {$this->keyPrefixStr}{$key} to be dictionary, but is list" );
		}
		$result = clone $this;
		$result->conf = $data ?? [];
		$result->keyPrefixStr = $this->keyPrefixStr . $key . self::KEY_SEPARATOR;
		$result->keyPrefix[] = $key;

		return $result;
	}

	public function getList( string $key ): array
	{
		$data = $this->getListOrNull( $key );

		return null === $data ? throw new DomainException( "Expecting config key {$this->keyPrefixStr}{$key} to be list, but is null" ) : $data;
	}

	public function getListOrEmpty( string $key ): array
	{
		return $this->getListOrNull( $key ) ?? [];
	}

	public function getListOrNull( string $key ): ?array
	{
		$data = $this->getField( $key );
		if ( null === $data ) {
			return null;
		}
		if ( !\is_array( $data ) ) {
			throw new DomainException( "Expecting config key {$this->keyPrefixStr}{$key} to be list, but is " . \gettype( $data ) );
		}
		if ( \is_string( \key( $data ) ) ) {
			throw new DomainException( "Expecting config key {$this->keyPrefixStr}{$key} to be list, but is dictionary" );
		}
		foreach ( $data as $k => &$value ) {
			if ( \is_array( $value ) && \is_string( \key( $value ) ) ) {
				$value = clone $this;
				$value->conf = $value;
				$value->keyPrefixStr .= "{$key}[{$k}]";
				$value->keyPrefix[] = $key;
				$value->keyPrefix[] = $k;
			}
		}

		return $data;
	}

	public function getStringOrNull( string $key ): ?string
	{
		$data = $this->getField( $key );
		if ( null === $data ) {
			return null;
		}
		if ( !\is_string( $data ) ) {
			throw new DomainException( "Expecting config key {$this->keyPrefixStr}{$key} to be string, but is " . \gettype( $data ) );
		}

		return $data;
	}

	public function getNumeric( string $key ): string|int|float
	{
		$data = $this->getField( $key );
		if ( !\is_numeric( $data ) ) {
			throw new DomainException( "Expecting config key {$this->keyPrefixStr}{$key} to be numeric, but is " . \gettype( $data ) );
		}

		return $data;
	}

	public function getNumericOrNull( string $key ): string|int|float|null
	{
		$data = $this->getField( $key );
		if ( null === $data || !\is_numeric( $data ) ) {
			throw new DomainException( "Expecting config key {$this->keyPrefixStr}{$key} to be numeric, but is " . \gettype( $data ) );
		}

		return $data;
	}

	protected function getField( string $key ): mixed
	{
		if ( \array_key_exists( $key, $this->conf ) ) {
			return $this->conf[$key];
		}

		return null;
	}
}
