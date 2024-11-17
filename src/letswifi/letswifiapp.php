<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi;

use PDO;
use RuntimeException;
use Throwable;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;
use fyrkat\openssl\PKCS7;
use letswifi\auth\User;
use letswifi\credential\UserCredentialManager;
use letswifi\provider\Provider;
use letswifi\provider\Realm;
use letswifi\provider\TenantConfig;

final class LetsWifiApp
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

	/** @var bool */
	private $crashing = false;

	/** @var ?PDO */
	private $pdo;

	/** @var ?\Twig\Environment */
	private $twig;

	/** @var TenantConfig */
	private $tenantConfig;

	public function __construct( public readonly string $basePath, private readonly LetsWifiConfig $config = new LetsWifiConfig() )
	{
		$this->tenantConfig = new TenantConfig( $this->config );
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
		$message = $ex::class . ': ' . $ex->getMessage();
		if ( \PHP_SAPI !== 'cli' && !\headers_sent() ) {
			\header( 'Content-Type: text/plain', true, $code );
			if ( !$this->crashing ) {
				$this->crashing = true;

				try {
					$data = ['code' => $code, 'code_explain' => $codeExplain, 'message' => $message];
					if ( \PHP_SAPI === 'cli-server' ) {
						$data['stacktrace'] = $ex;
					}
					$this->render(
						$data,
						template: 'error',
						basePath: $this->basePath,
					);
				} catch ( LoaderError $_ ) {
				}
			}
		}
		echo "{$code} {$codeExplain}\r\n\r\n{$message}\r\n";

		exit( 1 );
	}

	public function render( array $data, ?string $template = null, ?string $basePath = '/' ): void
	{
		if ( null === $template || \array_key_exists( 'json', $_GET ) || !$this->isBrowser() ) {
			\header( 'Content-Type: application/json' );

			exit( \json_encode( $data, \JSON_UNESCAPED_SLASHES ) . "\r\n" );
		}
		\header( 'Content-Type: text/html;charset=utf8' );

		$template = $this->getTwig()->load( "{$template}.html" );

		exit( $template->render( ['_basePath' => $basePath] + $data ) );
	}

	public static function getHttpHost(): string
	{
		if ( !\array_key_exists( 'HTTP_HOST', $_SERVER ) ) {
			throw new RuntimeException( 'No HTTP Host: header provided' );
		}

		return $_SERVER['HTTP_HOST'];
	}

	public function getTwig(): Environment
	{
		if ( null === $this->twig ) {
			$loader = new FilesystemLoader(
				$this->config->getListOrNull( 'tpldir' )
				?? [\implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 2 ), 'tpl'] )],
			);
			$this->twig = new Environment( $loader, [
				// 'cache' => '/path/to/compilation_cache',
			] );
		}

		return $this->twig;
	}

	public function getProvider(): Provider
	{
		return $this->tenantConfig->getProvider( $this->getHttpHost() );
	}

	public function getPDO(): PDO
	{
		if ( null === $this->pdo ) {
			$pdoData = $this->config->getDictionary( 'pdo' );
			$dsn = $pdoData->getString( 'dsn' );
			$username = $pdoData->getStringOrNull( 'username' );
			$password = $pdoData->getStringOrNull( 'password' );

			$this->pdo = new PDO( $dsn, $username, $password );
			$this->pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		}

		return $this->pdo;
	}

	public function getUserCredentialManager( User $user, Realm $realm, ?Provider $provider = null ): UserCredentialManager
	{
		return new UserCredentialManager(
			user: $user,
			realm: $realm,
			provider: $provider ?? $this->getProvider(),
			config: $this->config,
			pdo: $this->getPDO(),
		);
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
		$signingCert = $this->config->getStringOrNull( 'profile.signing.cert' );
		$signingKey = $this->config->getStringOrNull( 'profile.signing.key' );
		$passphrase = $this->config->getStringOrNull( 'profile.signing.passphrase' );
		if ( null === $signingCert || null === $signingKey ) {
			return null;
		}

		return PKCS7::readChainPEM( $signingCert . "\n" . $signingKey, $passphrase );
	}
}
