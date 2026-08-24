<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\commandline;

use InvalidArgumentException;

/** @internal */
class Table
{
	public readonly array $headerColumns;

	public readonly int $headerCount;

	protected array $columnWidths;

	protected array $table = [];

	public function __construct( string ...$headerColumns )
	{
		$this->headerColumns = $headerColumns;
		$this->headerCount = \count( $headerColumns );
		$this->columnWidths = \array_map( 'strlen', $headerColumns );
	}

	public function __toString(): string
	{
		return $this->printTable();
	}

	public function add( string ...$columns ): void
	{
		if ( \count( $this->headerColumns ) !== ( $columnCount = \count( $columns ) ) ) {
			throw new InvalidArgumentException( "Amount of arguments ({$columnCount}) must be equal to amount of headers ({$this->headerCount})" );
		}
		$this->table[] = $columns;
		for ( $i = 0; $i < $columnCount; ++$i ) {
			$this->columnWidths[$i] = \max( $this->columnWidths[$i], \strlen( $columns[$i] ) );
		}
	}

	public function printTsv( bool $header = true ): string
	{
		$output = $header ? [
			\implode(
				"\t",
				\array_map(
					static fn( string $h ) => \strtoupper( \strtr( $h, '_', ' ' ) ),
					$this->headerColumns,
				) ),
		] : [];

		foreach ( $this->table as $row ) {
			$output[] = \implode( "\t", $row );
		}

		return \implode( \PHP_EOL, $output ) . \PHP_EOL;
	}

	public function printTable( int $margin = 0, bool $header = true ): string
	{
		$output = $header ? [
			\rtrim( \implode(
				$margin ? '' : "\t",
				\array_map(
					fn( int $i ) => \str_pad(
						\strtoupper( \strtr( $this->headerColumns[$i], '_', ' ' ) ),
						$this->columnWidths[$i] + $margin,
					),
					\range( 0, $this->headerCount - 1 ),
				),
			) ),
		] : [];

		foreach ( $this->table as $row ) {
			$output[] = \rtrim( \implode(
				$margin ? '' : "\t",
				\array_map(
					fn( int $i ) => \str_pad(
						(string)( $row[$i] ?? '' ),
						$this->columnWidths[$i] + $margin,
					),
					\range( 0, $this->headerCount - 1 ),
				),
			) );
		}

		return \implode( \PHP_EOL, $output ) . \PHP_EOL;
	}
}
