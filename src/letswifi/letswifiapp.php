<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2021, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * Copyright: 2020-2021, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi;

use DomainException;

use fkooman\Template\Tpl;

use fyrkat\oauth\Client;
use fyrkat\oauth\OAuth;
use fyrkat\oauth\sealer\JWTSealer;
use fyrkat\oauth\sealer\PDOSealer;
use fyrkat\oauth\token\AccessToken;
use fyrkat\oauth\token\AuthorizationCode;
use fyrkat\oauth\token\Grant;
use fyrkat\oauth\token\RefreshToken;

use letswifi\browserauth\BrowserAuthInterface;

use letswifi\realm\Realm;
use letswifi\realm\RealmManager;
use letswifi\realm\User;

use PDO;
use RuntimeException;
use Throwable;

class LetsWifiApp
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

	/** @var ?RealmManager */
	private $realmManager;

	/** @var ?Tpl */
	private $tpl;

	public function __construct( Config $config = null )
	{
		$this->config = $config ?? new Config();
	}

	public function getUserFromBrowserSession( Realm $realm ): User
	{
		$auth = $this->getBrowserAuthenticator( $realm );
		$userId = $auth->requireAuth();
		$userRealmPrefix = $auth->getUserRealmPrefix();

		return new User( $userId, null, $this->getIP(), $_SERVER['HTTP_USER_AGENT'], $userRealmPrefix );
	}

	public function getUserFromGrant( Grant $grant ): User
	{
		$sub = $grant->getSub();
		if ( null === $sub ) {
			throw new DomainException( 'No user subject available' );
		}
		$sub_values = \explode( ',', $sub );

		if ( 2 === \count( $sub_values ) ) {
			return new User( $sub_values[0], $grant->getClientId(), $this->getIP(), $_SERVER['HTTP_USER_AGENT'], $sub_values[1] );
		}

		return new User( $sub, $grant->getClientId(), $this->getIP(), $_SERVER['HTTP_USER_AGENT'] );
	}

	public function getIP(): string
	{
		return $_SERVER['REMOTE_ADDR'];
	}

	public function getBrowserAuthenticator( Realm $realm ): BrowserAuthInterface
	{
		$params = $this->config->getArrayOrEmpty( 'auth.params' );
		$service = $this->config->getString( 'auth.service' );
		$realmParams = $this->config->getArrayOrEmpty( 'realm.auth' );
		if ( \array_key_exists( $realm->getName(), $realmParams ) ) {
			$params = \array_merge( $params, $realmParams[$realm->getName()] );
		}

		if ( !\preg_match( '/[A-Z][A-Za-z0-9]+/', $service ) ) {
			throw new DomainException( 'Illegal auth.service specified in config' );
		}
		$service = 'letswifi\\browserauth\\' . $service;
		$result = new $service( $params );
		if ( $result instanceof BrowserAuthInterface ) {
			return $result;
		}
		throw new DomainException( 'auth.service must point to a class that implements BrowserAuthInterface' );
	}

	public function getSigningCertificate(): ?string
	{
		return $this->config->getStringOrNull( 'signing.cert' );
	}

	public function getOAuthHandler( Realm $realm ): OAuth
	{
		$accessTokenSealer = new JWTSealer( AccessToken::class, $realm->getSecretKey() );
		$authorizationCodeSealer = new JWTSealer( AuthorizationCode::class, $realm->getSecretKey() );
		$refreshTokenSealer = new PDOSealer( RefreshToken::class, $this->getPDO() );

		$oauth = new OAuth(
				$accessTokenSealer,
				$authorizationCodeSealer,
				$refreshTokenSealer,
			);
		foreach ( $this->config->getArray( 'oauth.clients' ) as $client ) {
			$oauth->registerClient( new Client( $client['clientId'], $client['redirectUris'], $client['scopes'], $client['refresh'] ?? false ) );
		}

		return $oauth;
	}

	public function getRealm( string $realmName = null ): Realm
	{
		if ( null === $realmName ) {
			$realmName = $this->getCurrentRealmName();
		}

		return $this->getRealmManager()->getRealm( $realmName );
	}

	public function getRealmManager(): RealmManager
	{
		if ( null === $this->realmManager ) {
			$this->realmManager = new RealmManager( $this->getPDO() );
		}

		return $this->realmManager;
	}

	public function guessRealm( Realm $baseRealm ): ?Realm
	{
		$auth = $this->getBrowserAuthenticator( $baseRealm );
		$guess = $auth->guessRealm( $this->config->getArrayOrEmpty( 'realm.auth' ) );

		return $guess ? $this->getRealm( $guess ) : null;
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
		if ( \PHP_SAPI !== 'cli' ) {
			\header( 'Content-Type: text/plain', true, $code );
		}
		echo "${code} ${codeExplain}\r\n\r\n${message}\r\n";
	}

	public function render( array $data, ?string $template = null ): void
	{
		$accept = \array_key_exists( 'HTTP_ACCEPT', $_SERVER ) ? $_SERVER['HTTP_ACCEPT'] : '';
		if ( null === $template || \array_key_exists( 'json', $_GET ) || false === \strpos( $accept, 'text/html' ) ) {
			\header( 'Content-Type: application/json' );
			exit( \json_encode( $data ) );
		}
		exit( $this->getTemplate()->render( $template, $data ) );
	}

	protected function getTemplate(): Tpl
	{
		if ( null === $this->tpl ) {
			$dirs = $this->config->getArrayOrNull('tpldir') ?? [\implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 2 ), 'tpl'])];
			$this->tpl = new Tpl( $dirs );
		}

		return $this->tpl;
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

	private function getCurrentRealmName(): string
	{
		switch ( $this->config->getStringOrNull( 'realm.selector' ) ) {
			case 'getparam': return $this->getCurrentRealmNameFromGetParam();
			case 'httphost': return $this->getCurrentRealmNameFromHttpHost();
			default: return $this->config->getString( 'realm.default' );
		}
	}

	private function getCurrentRealmNameFromHttpHost(): string
	{
		$httpHost = $_SERVER['HTTP_HOST'];
		$realm = $this->getRealmManager()->getRealmNameByHttpHost( $httpHost );
		if ( null === $realm ) {
			throw new RuntimeException( "No realm set for HTTP hostname ${httpHost}" );
		}

		return $realm;
	}

	private function getCurrentRealmNameFromGetParam(): string
	{
		if ( !\array_key_exists( 'realm', $_GET ) ) {
			throw new RuntimeException( 'No realm set' );
		}
		if ( \is_string( $_GET['realm'] ) ) {
			return $_GET['realm'];
		}
		throw new RuntimeException( 'realm parameter must be string' );
	}
}
