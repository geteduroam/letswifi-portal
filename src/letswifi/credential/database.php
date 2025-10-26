<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\credential;

use PDO;

/** @internal */
trait Database
{
	private ?PDO $pdo = null;

	protected function getPDO(): PDO
	{
		if ( null === $this->pdo ) {
			// TODO $this->config is a loose dependency
			$providers = $this->config->getDictionary( 'provider' );
			$pdoData = $providers->getDictionary( $this->provider->host )->getDictionary( 'pdo' );
			$dsn = $pdoData->getString( 'dsn' );
			$username = $pdoData->getStringOrNull( 'username' );
			$password = $pdoData->getStringOrNull( 'password' );

			$this->pdo = new PDO( $dsn, $username, $password );
			$this->pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

			if ( \strstr( $dsn, ':', true ) === 'mysql' ) {
				// https://dev.mysql.com/doc/refman/8.4/en/set-variable.html
				// https://dev.mysql.com/doc/refman/8.4/en/sql-mode.html#sqlmode_ansi_quotes
				// TODO do we override existing modes this way, do we want to keep them, how?
				$this->pdo->exec( 'SET SESSION sql_mode = \'ANSI_QUOTES\';' );
			}
		}

		return $this->pdo;
	}
}
