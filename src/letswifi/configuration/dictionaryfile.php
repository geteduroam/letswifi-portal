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
	protected string $dir;

	protected string $baseDir;

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
		$this->dir = \dirname( $file );
		$this->baseDir = \realpath( $this->dir );
	}

	public function offsetExists( mixed $offset ): bool
	{
		return parent::offsetExists( $offset )
		|| ( parent::offsetExists( "{$offset}#inc" ) && \is_readable( $this->safePath( parent::offsetGet( "{$offset}#inc" ), $offset ) ) )
		|| ( parent::offsetExists( "{$offset}#file" ) && \is_readable( $this->safePath( parent::offsetGet( "{$offset}#file" ), $offset ) ) )
		|| ( parent::offsetExists( "{$offset}#dir" ) && \is_dir( $this->safePath( parent::offsetGet( "{$offset}#dir" ), $offset ) ) )
		|| ( parent::offsetExists( "{$offset}#pemdir" ) && \is_dir( $this->safePath( parent::offsetGet( "{$offset}#pemdir" ), $offset ) ) );
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
		// No support for #dir, these can be lazy loaded
		// No support for #pemdir, these can be lazy loaded

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
			throw new ConfigurationException( $this->getConfigPath( $key ) . ': Key does not exist' );
		}

		$result->parentKeys[] = $key;

		return $result;
	}

	protected function safePath( string $path, string $key ): string
	{
		if ( !\file_exists( $this->dir . \DIRECTORY_SEPARATOR . $path ) ) {
			throw new ConfigurationException( $this->getConfigPath( $key ) . ": {$path}: Path not found" );
		}
		$realPath = \realpath( $this->dir . \DIRECTORY_SEPARATOR . $path );
		if ( false === $realPath || !\str_starts_with( $realPath, $this->baseDir ) ) {
			throw new ConfigurationException( $this->getConfigPath( $key ) . ": Invalid path \"{$path}\"" );
		}

		return $realPath;
	}
}
