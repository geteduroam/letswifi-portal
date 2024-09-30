<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2022, Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: 2020-2022, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\realm;

use InvalidArgumentException;
use LogicException;
use PDO;

class DatabaseStorage
{
	/** @var PDO */
	protected $pdo;

	public function __construct( PDO $pdo )
	{
		$this->pdo = $pdo;
	}

	/**
	 * @throws InvalidArgumentException
	 */
	protected static function safeString( string $string, string $type = '' ): void
	{
		if ( !\preg_match( '@^([a-z0-9_-])+$@i', $string ) ) {
			throw new InvalidArgumentException( "Illegal {$type} string: {$string}" );
		}
	}

	/**
	 * @param string                   $table
	 * @param string                   $field
	 * @param array<string,int|string> $where
	 *
	 * @return mixed
	 */
	protected function getSingleFieldFromTableWhere( $table, $field, $where )
	{
		$entry = $this->getSingleEntryFromTableWhere( $table, $where );
		if ( !\is_array( $entry ) ) {
			return;
		}
		if ( !\array_key_exists( $field, $entry ) ) {
			throw new LogicException( "Database entry did not return field {$field} in table {$table}" );
		}

		return $entry[$field];
	}

	/**
	 * @suppress PhanUnextractableAnnotationSuffix Phan doesn't understand ?array<array-key,mixed>
	 *
	 * @param string                   $table
	 * @param array<string,int|string> $where
	 *
	 * @return ?array<array-key,mixed>
	 */
	protected function getSingleEntryFromTableWhere( string $table, array $where ): ?array
	{
		$entry = $this->getEntriesFromTableWhere( $table, $where );
		if ( \count( $entry ) > 1 ) {
			throw new LogicException( 'Got more than one result in a query that should return at most one result' );
		}

		$result = \reset( $entry );
		if ( false === $result ) {
			return null;
		}

		return $result;
	}

	/**
	 * @param string                                     $table
	 * @param string                                     $field
	 * @param array<string,array<int|string>|int|string> $where
	 *
	 * @return array<mixed>
	 */
	protected function getFieldsFromTableWhere( string $table, string $field, array $where ): array
	{
		$entries = $this->getEntriesFromTableWhere( $table, $where );
		$result = [];
		foreach ( $entries as $entry ) {
			if ( !\array_key_exists( $field, $entry ) ) {
				throw new LogicException( "Database entry did not return field {$field} in table {$table}" );
			}
			$result[] = $entry[$field];
		}

		return $result;
	}

	/**
	 * @suppress PhanUnextractableAnnotationSuffix Phan doesn't understand array<array<array-key,mixed>>
	 * @suppress PhanPossiblyNonClassMethodCall Phan doesn't understand PDO
	 * @suppress PhanPossiblyFalseTypeReturn Phan doesn't understand PDO
	 *
	 * @param string                                     $table
	 * @param array<string,array<int|string>|int|string> $where
	 *
	 * @return array<array<array-key,mixed>>
	 */
	protected function getEntriesFromTableWhere( string $table, array $where ): array
	{
		static::safeString( $table, 'SQL table' );
		$query = "SELECT * FROM `{$table}`";
		$first = true;
		$bind = [];
		foreach ( $where as $key => $value ) {
			static::safeString( $key, 'SQL field name' );
			if ( \is_array( $value ) ) {
				foreach ( $value as $vkey => $vvalue ) {
					$bind[$key . $vkey] = $vvalue;
				}
			} else {
				$bind[$key] = $value;
			}
			$query .= $first ? ' WHERE ' : ' AND ';
			$first = false;
			switch ( $key ) {
				case 'issued': $query .= '`issued` < :issued';
					break;
				case 'expires': $query .= '(`expires` > :expires OR `expires` IS NULL)';
					break;
				default: $query .= \is_array( $value )
					? "`{$key}` IN (:" . $key . \implode( ",:{$key}", \array_keys( $value ) ) . ')'
					: "`{$key}` = :{$key}";
			}
		}

		$this->pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		$statement = $this->pdo->prepare( $query );
		$statement->execute( $bind );

		return $statement->fetchAll( PDO::FETCH_ASSOC );
	}
}
