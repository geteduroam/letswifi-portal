<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\configuration;

use ArrayAccess;
use OutOfBoundsException;
use fyrkat\multilang\MultiLanguageString;

/**
 * @implements \ArrayAccess<string,mixed>
 */
class Dictionary implements ArrayAccess
{
	public const KEY_SEPARATOR = '->';

	/** @var array<string> */
	protected array $parentKeys = [];

	/**
	 * @param array<string,mixed> $data PHP file
	 */
	public function __construct( protected array $data )
	{
	}

	/** Prevent leaking data via var_dump*/
	public function __debugInfo(): array
	{
		return [];
	}

	public function getParentKey(): string
	{
		return \end( $this->parentKeys ) ?: throw new OutOfBoundsException( 'This object is the root configuration node' );
	}

	public function getConfigPath( ?string $offset = null ): string
	{
		return \implode( self::KEY_SEPARATOR, \array_filter( $this->parentKeys + [\PHP_INT_MAX => $offset] ) );
	}

	final public function has( string $key ): bool
	{
		return $this->offsetExists( $key );
	}

	public function offsetExists( mixed $offset ): bool
	{
		return \array_key_exists( $offset, $this->data ) && null !== $this->data[$offset];
	}

	public function offsetGet( mixed $offset ): mixed
	{
		return $this->data[$offset];
	}

	public function offsetSet( mixed $offset, mixed $value ): void
	{
		throw new ConfigurationException( $this->getConfigPath( $offset ) . ': Configuration is read-only' );
	}

	public function offsetUnset( mixed $offset ): void
	{
		throw new ConfigurationException( $this->getConfigPath( $offset ) . ': Configuration is read-only' );
	}

	public function getDictionaryOrNull( string $key ): ?self
	{
		return $this->has( $key ) ? $this->getDictionary( $key ) : null;
	}

	/**
	 * @param class-string<Dictionary> $class
	 *
	 * @return array<string,Dictionary>
	 */
	public function getDictionaryList( string $key, string $class = self::class ): array
	{
		// TODO: Add $this->has($key) check

		$result = [];
		foreach ( $this->getList( $key, $class ) as $k => $v ) {
			\assert( $v instanceof self );
			$v->parentKeys[] = $key;
			$v->parentKeys[] = $k;
			$result[$k] = $v;
		}

		return $result;
	}

	public function getDictionary( string $key ): self
	{
		if ( !$this->has( $key ) ) {
			throw new ConfigurationException( $this->getConfigPath( $key ) . ': Configuration not set' );
		}

		$result = $this->get( $key, self::class );
		\assert( $result instanceof self );
		$result->parentKeys[] = $key;

		return $result;
	}

	public function getString( string $key ): string
	{
		return $this->get( $key, '' );
	}

	public function getMultiLanguageString( string $key ): MultiLanguageString
	{
		\class_exists( MultiLanguageString::class, true ); // Triggers autoloader
		$result = $this->get( $key, MultiLanguageString::class );
		\assert( $result instanceof MultiLanguageString );

		return $result;
	}

	public function getMultiLanguageStringOrNull( string $key ): ?MultiLanguageString
	{
		return $this->has( $key ) ? $this->getMultiLanguageString( $key ) : null;
	}

	public function getStringOrNull( string $key ): ?string
	{
		return $this->has( $key ) ? $this->getString( $key ) : null;
	}

	/** @return array<string> */
	public function getStringArray( string $key ): array
	{
		return $this->getList( $key, '' );
	}

	public function getRawArray( string $key ): array
	{
		return $this->get( $key, [] );
	}

	public function getInteger( string $key ): int
	{
		return $this->get( $key, 0 );
	}

	public function getFloat( string $key ): float
	{
		return $this->get( $key, 0.0 );
	}

	/**
	 * @template T
	 *
	 * @param                   $key The key to retrieve the value for
	 * @param class-string<T>|T $t   Type of the expected return value, either an object, classname, or falsey scalar or array (e.g. `''`, `[]`, `0`, `0.0`, `false`)
	 *
	 * @return T
	 */
	protected function get( string $key, mixed $t ): mixed
	{
		return $this->getValue( $key, $this->has( $key ) ? $this->offsetGet( $key ) : null, $t );
	}

	/**
	 * @template T
	 *
	 * @param                   $key The key to retrieve the value for
	 * @param class-string<T>|T $t   Type of the expected return value, either an object, classname, or falsey scalar or array (e.g. `''`, `[]`, `0`, `0.0`, `false`)
	 *
	 * @return array<T>
	 */
	protected function getList( string $key, mixed $t ): array
	{
		$rawList = $this->has( $key ) ? $this->offsetGet( $key ) : [];

		return \array_map( fn( mixed $v ) => $this->getValue( $key, $v, $t ), $rawList );
	}

	/**
	 * @template T
	 *
	 * @param                   $key   The key to retrieve the value for
	 * @param                   $value The value found
	 * @param class-string<T>|T $t     Expected type of the value, either an object, classname, or falsey scalar or array (e.g. `''`, `[]`, `0`, `0.0`, `false`)
	 *
	 *@psalm-suppress InvalidReturnType
	 *
	 * @return T
	 */
	private function getValue( string $key, mixed $value, mixed $t ): mixed
	{
		/** @psalm-suppress InvalidReturnStatement */
		if ( !$t ) { // type is falsey so we return if value is of same type
			if ( \is_string( $t ) && \is_string( $value ) ) {
				return $value;
			}
			if ( \is_array( $t ) && \is_array( $value ) ) {
				return $value;
			}
			if ( \is_int( $t ) && \is_int( $value ) ) {
				return $value;
			}
			if ( \is_float( $t ) && ( \is_float( $value ) || \is_int( $value ) ) ) {
				return (float)$value;
			}
			if ( \is_bool( $t ) && \is_bool( $value ) ) {
				return $value;
			}

			throw new ConfigurationException( $this->getConfigPath( $key ) . ': Expected value of type ' . \gettype( $t ) . ' but got ' . \gettype( $value ) );
		}
		$class = null;
		if ( \is_object( $t ) ) {
			$class = $t::class;
		} elseif ( !\is_string( $t ) ) {
			throw new ConfigurationException( $this->getConfigPath( $key ) . ': $t must either be object, class or falsey scalar or array, got truthy ' . \gettype( $t ) );
		}
		if ( null === $class && \class_exists( $t, false ) ) {
			$class = $t;
		}
		if ( null === $class ) {
			throw new ConfigurationException( $this->getConfigPath( $key ) . ': $t must either be object, class or falsey scalar or array, got ' . \var_export( $t, true ) );
		}

		/** @psalm-suppress InvalidReturnStatement */
		return new $class( $value );
	}
}
