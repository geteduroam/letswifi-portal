<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2022, Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: 2020-2022, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi;

use DomainException;

use fyrkat\oauth\Client;
use fyrkat\oauth\OAuth;

use fyrkat\oauth\sealer\JWTSealer;
use fyrkat\oauth\sealer\PDOSealer;
use fyrkat\oauth\token\AccessToken;
use fyrkat\oauth\token\AuthorizationCode;
use fyrkat\oauth\token\Grant;
use fyrkat\oauth\token\RefreshToken;

use fyrkat\openssl\PKCS7;

use letswifi\browserauth\BrowserAuthInterface;

use letswifi\realm\Realm;
use letswifi\realm\RealmManager;
use letswifi\realm\User;

use PDO;
use RuntimeException;
use Throwable;

class LetsWifiApp
{
	public const HTTP_CODES = [
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

	/** @var ?\Twig\Environment */
	private $twig;

	public function __construct( Config $config = null )
	{
		$this->config = $config ?? new Config();
	}

	public function getUserFromBrowserSession( Realm $realm ): User
	{
		$auth = $this->getBrowserAuthenticator( $realm );
		$userId = $auth->requireAuth();
		$userRealm = $auth->getRealm();

		if ( null !== $userRealm && \strpos( $userRealm, '.' ) === false ) {
			// There is no . in $userRealm, so we assume it's a subrealm,
			// and append the current realm to it.
			$userRealm .= '.' . $this->getRealm()->getName();
		}
		$realm = $userRealm ?? $this->getRealm()->getName();
		\assert( '.' !== $realm[0], 'Realm cannot start with .' );

		return new User( $userId, $realm, null, $this->getIP(), $_SERVER['HTTP_USER_AGENT'] ?? null );
	}

	public function getUserFromGrant( Grant $grant ): User
	{
		$sub = $grant->getSub();

		$realm = $grant->realm;
		if ( empty( $realm ) ) {
			throw new DomainException( "User ${sub} has no realm" );
		}

		return new User( $sub, $realm, $grant->getClientId(), $this->getIP(), $_SERVER['HTTP_USER_AGENT'] ?? null );
	}

	public function isAdmin( string $userName ): bool
	{
		$admins = $this->config->getArrayOrEmpty( 'auth.admin' );

		return \in_array( $userName, $admins, true);
	}

	public function getIP(): string
	{
		\assert( \array_key_exists( 'REMOTE_ADDR', $_SERVER ) );

		return $_SERVER['REMOTE_ADDR'];
	}

	public function isBrowser(): bool
	{
		return \substr( $_SERVER['HTTP_ACCEPT'] ?? '', 0, 9 ) === 'text/html';
	}

	public function requireAdmin( string $scope ): void
	{
		$realm = $this->getRealm();

		if ( $this->isBrowser() ) {
			$user = $this->getUserFromBrowserSession( $realm )->getUserId();
		} else {
			$oauth = $this->getOAuthHandler( $realm );
			$token = $oauth->getAccessTokenFromRequest( $scope );
			$grant = $token->getGrant();
			$user = $grant->sub;
		}

		if ( null === $user ) {
			\header( 'Content-Type: text/plain', true, 403 );
			exit( "403 Forbidden\r\n\r\nUnauthenticated\r\n" );
		}
		if ( !$this->isAdmin( $user ) ) {
			\header( 'Content-Type: text/plain', true, 403 );
			exit( "403 Forbidden\r\n\r\nNo admin access for ${user}\r\n" );
		}
	}

	public function getBrowserAuthenticator( Realm $realm ): BrowserAuthInterface
	{
		$params = $this->config->getArrayOrEmpty( 'auth.params' );
		$service = $this->config->getString( 'auth.service' );
		$realmParams = $this->config->getArrayOrEmpty( 'realm.auth' );
		if ( \array_key_exists( $realm->getName(), $realmParams ) ) {
			$params = \array_merge( $params, $realmParams[$realm->getName()] );
		}

		if ( !\preg_match( '/^[A-Z][A-Za-z0-9]+$/', $service ) ) {
			throw new DomainException( 'Illegal auth.service specified in config' );
		}
		$service = 'letswifi\\browserauth\\' . $service;
		$result = new $service( $params );
		if ( $result instanceof BrowserAuthInterface ) {
			return $result;
		}
		throw new DomainException( 'auth.service must point to a class that implements BrowserAuthInterface' );
	}

	/**
	 * Get the signer for signing profiles
	 *
	 * This is used for signing mobileconfig files, so that Apple OSes don't show a
	 * big red "not signed" warning when installing the profile.
	 *
	 * This function reads both the signing.cert config setting (expecting a path)
	 * and the profile.signing.cert setting (expecting a payload),
	 * the latter is preferred.
	 *
	 * A config setting profile.signing.passphrase is also used to decode the private key,
	 * if it is protected by a passphrase.
	 *
	 * @return ?PKCS7 if the signer if configured, otherwise NULL
	 */
	public function getProfileSigner(): ?PKCS7
	{
		$signingCert = $this->config->getStringOrNull( 'signing.cert' );
		if ( null !== $signingCert ) {
			// Reading files is a lot of hassle to do correcly,
			// as demonstrated by the large amount of code in this block.
			// In a future version, we will remove support for signing.cert,
			// and only support profile.signing.*

			\error_log( 'signing.cert configuration entry set, please migrate to profile.signing.cert, which takes the raw certificate payload' );
			if ( \file_exists( $signingCert ) ) {
				$signingCert = \file_get_contents( $signingCert );
				if ( false === $signingCert ) {
					throw new RuntimeException( 'File specified in signing.cert is unreadable, please migrate to profile.signing.cert' );
				}
			} else {
				throw new RuntimeException( 'File specified in signing.cert does not exist, please migrate to profile.signing.cert' );
			}
		} else {
			$signingCert = $this->config->getStringOrNull( 'profile.signing.cert' );
			$signingKey = $this->config->getStringOrNull( 'profile.signing.key' );
			if ( null !== $signingCert && null !== $signingKey ) {
				$signingCert .= "\n${signingKey}";
			}
		}
		if ( null === $signingCert ) {
			return null;
		}

		$passphrase = $this->config->getStringOrNull( 'profile.signing.passphrase' );

		return PKCS7::readChainPEM( $signingCert, $passphrase );
	}

	/**
	 * @psalm-suppress ArgumentTypeCoercion Psalm incorrectly things JWTSealer is parent of Sealer, it's the other way around
	 */
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
			$oauth->registerClient( new Client(
						$client['clientId'],
						$client['redirectUris'] ?? [],
						$client['scopes'],
						$client['refresh'] ?? false,
						$client['clientSecret'] ?? null,
					),
				);
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
		exit(1);
	}

	public function render( array $data, ?string $template = null, ?string $basePath = '/' ): void
	{
		if ( null === $template || \array_key_exists( 'json', $_GET ) || !$this->isBrowser() ) {
			\header( 'Content-Type: application/json' );
			exit( \json_encode( $data, \JSON_UNESCAPED_SLASHES ) . "\r\n" );
		}
		$template = $this->getTwig()->load( "${template}.html" );
		exit( $template->render( ['_basePath' => $basePath] + $data ) );
	}

	public static function getHttpHost(): string
	{
		if ( !\array_key_exists( 'HTTP_HOST', $_SERVER ) ) {
			throw new RuntimeException( 'No HTTP Host: header provided' );
		}

		return $_SERVER['HTTP_HOST'];
	}

	protected function getTwig(): \Twig\Environment
	{
		if ( null === $this->twig ) {
			$loader = new \Twig\Loader\FilesystemLoader(
				$this->config->getArrayOrNull( 'tpldir' )
				?? [\implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 2 ), 'tpl'] )],
			);
			$this->twig = new \Twig\Environment( $loader, [
				//'cache' => '/path/to/compilation_cache',
			] );
		}

		return $this->twig;
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
			case 'httphost': return $this->getCurrentRealmNameFromHttpHost();
			default: return $this->config->getString( 'realm.default' );
		}
	}

	private function getCurrentRealmNameFromHttpHost(): string
	{
		$httpHost = $this->getHttpHost();
		$realm = $this->getRealmManager()->getRealmNameByHttpHost( $httpHost );
		if ( null === $realm ) {
			throw new RuntimeException( "No realm set for HTTP hostname ${httpHost}" );
		}

		return $realm;
	}
}
