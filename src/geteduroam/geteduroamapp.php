<?php declare(strict_types=1);

/*
 * This file is part of geteduroam; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2020, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace geteduroam;

use fyrkat\oauth\Client;
use fyrkat\oauth\JWTSigner;
use fyrkat\oauth\OAuth;

use geteduroam\Credential\User;
use geteduroam\Realm\Realm;

use PDO;
use Throwable;

class GetEduroamApp
{
	const HTTP_CODES = [
		400 => 'Bad Request',
		401 => 'Unauthorized',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		417 => 'Expectation Failed',
		500 => 'Internal Server Error',
	];

	/** @var Config */
	private $config;

	/** @var ?PDO */
	private $pdo;

	public function __construct( Config $config = null )
	{
		$this->config = $config ?? new Config();
	}

	public function getCurrentUser(): User
	{
		return new User( $this->getOAuthHandler()->getAccessTokenFromRequest()->getSubject(), [] );
	}

	public function getOAuthHandler(): OAuth
	{
		$oauth = new OAuth( new JWTSigner( $this->config->getString( 'oauth.jwt.key' ) ) );
		foreach ( $this->config->getArray( 'oauth.clients' ) as $client ) {
			$oauth->registerClient( new Client( $client['clientId'], $client['redirectUris'], $client['scopes'] ) );
		}

		return $oauth;
	}

	public function getRealm( string $domain ): Realm
	{
		return new Realm( $this->getPDO(), $domain );
	}

	public function registerExceptionHandler(): void
	{
		\set_exception_handler( [$this, 'handleException'] );
	}

	public function handleException( Throwable $ex ): void
	{
		\error_log( $ex->__toString() );
		$code = $ex->getCode();
		if ( !\is_int( $code ) || !\array_key_exists( $code, static::HTTP_CODES ) ) {
			$code = 500;
		}
		$codeExplain = static::HTTP_CODES[$code];
		$message = $ex->getMessage();
		\header( 'Content-Type: text/plain', true, $code );
		echo "${code} ${codeExplain}\r\n\r\n${message}\r\n";
	}

	protected function getPDO(): PDO
	{
		if ( null === $this->pdo ) {
			$dsn = $this->config->getString( 'pdo.dsn' );
			$username = $this->config->getStringOrNull( 'pdo.username' );
			$password = $this->config->getStringOrNull( 'pdo.password' );

			$this->pdo = new PDO( $dsn, $username, $password );
			$this->pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		}

		return $this->pdo;
	}
}
