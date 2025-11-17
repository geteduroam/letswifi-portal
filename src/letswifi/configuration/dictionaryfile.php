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

class DictionaryFile extends Dictionary
{
	public string $dir;

	public string $baseDir;

	public function __construct( string $file )
	{
		if ( !\file_exists( $file ) ) {
			throw new DomainException( 'Configuration file missing: ' . $file );
		}
		$conf = require $file;
		if ( !\is_array( $conf ) ) {
			throw new DomainException( 'Configuration file should return array' );
		}
		parent::__construct( $conf );
		$this->dir = \dirname( $file ) . \DIRECTORY_SEPARATOR;
		$this->baseDir = \realpath( $this->dir ) . \DIRECTORY_SEPARATOR;
	}

	public function offsetExists( mixed $offset ): bool
	{
		return parent::offsetExists( $offset )
		|| ( parent::offsetExists( "{$offset}#inc" ) && \is_readable( $this->safePath( parent::offsetGet( "{$offset}#inc" ), $offset ) ) )
		|| ( parent::offsetExists( "{$offset}#file" ) && \is_readable( $this->safePath( parent::offsetGet( "{$offset}#file" ), $offset ) ) )
		|| ( parent::offsetExists( "{$offset}#dir" ) && \is_dir( $this->safePath( parent::offsetGet( "{$offset}#dir" ), $offset ) ) )
		|| ( parent::offsetExists( "{$offset}#pemdir" ) && \is_dir( $this->safePath( parent::offsetGet( "{$offset}#pemdir" ), $offset ) ) );
	}

	/**
	 * @param class-string<Dictionary> $class
	 *
	 * @return array<string,Dictionary>
	 */
	public function getDictionaryList( string $key, ?string $class = null ): array
	{
		$class ??= parent::offsetExists( $key ) ? parent::class : self::class;

		return parent::getDictionaryList( $key, $class );
	}

	public function offsetGet( mixed $offset ): mixed
	{
		if ( parent::offsetExists( $offset ) ) {
			return parent::offsetGet( $offset );
		}
		if ( parent::offsetExists( "{$offset}#inc" ) ) {
			/** @psalm-suppress UnresolvableInclude */
			return include $this->safePath( parent::get( "{$offset}#inc", '' ), $offset );
		}
		if ( parent::offsetExists( "{$offset}#file" ) ) {
			return \file_get_contents( $this->safePath( parent::get( "{$offset}#file", '' ), $offset ) );
		}
		if ( parent::offsetExists( "{$offset}#dir" ) ) {
			return $this->offsetGetDir( $offset );
		}

		return null;
	}

	public function getDictionary( string $key ): Dictionary
	{
		$result = null;
		if ( parent::offsetExists( $key ) ) {
			$result = clone $this;
			$result->data = parent::get( $key, [] );
		} elseif ( parent::offsetExists( "{$key}#inc" ) ) {
			$result = new self( $this->safePath( parent::get( "{$key}#inc", '' ), $key ) );
			$result->dir = $this->dir;
			$result->baseDir = $this->baseDir;
			$result->parentKeys = $this->parentKeys;
		}
		// No support for $key#file
		elseif ( parent::offsetExists( "{$key}#pem" ) ) {
			$result = new DictionaryPemFile( $this->safePath( parent::get( "{$key}#pem", '' ), $key ) );
			$result->parentKeys = $this->parentKeys;
		} elseif ( parent::offsetExists( "{$key}#dir" ) ) {
			$result = new DictionaryDir( $this->safePath( parent::get( "{$key}#dir", '' ), $key ) );
			$result->baseDir = $this->baseDir;
			$result->parentKeys = $this->parentKeys;
		} elseif ( parent::offsetExists( "{$key}#pemdir" ) ) {
			$result = new DictionaryPemDir( $this->safePath( parent::get( "{$key}#pemdir", '' ), $key ) );
			$result->baseDir = $this->baseDir;
			$result->parentKeys = $this->parentKeys;
		}

		if ( null === $result ) {
			throw new ConfigurationException( $this->getConfigPath( $key ) . ': Configuration not set' );
		}

		$result->parentKeys[] = $key;

		return $result;
	}

	protected function getList( string $key, mixed $t ): array
	{
		$result = [];
		$list = parent::getList( $key, $t );
		foreach ( $list as $k => $v ) {
			if ( \is_int( $k ) ) {
				return $list;
			}
			$result[\strstr( $k, '#', true ) ?: $k] = $v;
		}

		return $result;
	}

	protected function safePath( string $path, string $key ): string
	{
		$candidatePath = $this->dir . $path;
		if ( !\file_exists( $candidatePath ) ) {
			$candidatePath = $this->baseDir . $path;
			if ( !\file_exists( $candidatePath ) ) {
				throw new ConfigurationException( $this->getConfigPath( $key ) . ": {$path}: Path not found" );
			}
		}
		$realPath = \realpath( $candidatePath );
		if ( false === $realPath || !\str_starts_with( $realPath, $this->baseDir ) ) {
			throw new ConfigurationException( $this->getConfigPath( $key ) . ": Invalid path \"{$path}\"" );
		}

		return $realPath;
	}

	private function offsetGetDir( mixed $offset ): array
	{
		$dir = parent::get( "{$offset}#dir", '' );
		$list = DictionaryDir::createList( $this->safePath( $dir, $offset ) );
		foreach ( $list as $key => &$value ) {
			if ( \str_ends_with( $key, '#inc' ) ) {
				$value = $this->safePath( "{$dir}/{$value}", $key );
			}
		}

		return $list;
	}
}
