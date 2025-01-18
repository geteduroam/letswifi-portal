<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi;

use RuntimeException;
use Throwable;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;
use fyrkat\multilang\TranslationContext;
use fyrkat\openssl\PKCS7;
use letswifi\auth\User;
use letswifi\credential\UserCredentialLog;
use letswifi\tenant\Provider;
use letswifi\tenant\Realm;
use letswifi\tenant\TenantConfig;

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

	/** Fallback locale used when the Accept-Language header was not set */
	public const FALLBACK_LOCALE = 'en';

	/** Prevent endless loop if an exception occurs when rendering the error page */
	private bool $crashing = false;

	private ?Environment $twig = null;

	private TenantConfig $tenantConfig;

	private ?TranslationContext $translationContext = null;

	private readonly LetsWifiConfig $config;

	public function __construct( public readonly string $basePath, ?LetsWifiConfig $config = null )
	{
		$this->config = $config ?? new LetsWifiConfig( new configuration\DictionaryFile( \dirname( __DIR__, 2 ) . \DIRECTORY_SEPARATOR . 'etc' . \DIRECTORY_SEPARATOR . 'tenant.conf.php' ) );
		$this->tenantConfig = new TenantConfig( $this->config );
	}

	public function getIP(): string
	{
		\assert( \array_key_exists( 'REMOTE_ADDR', $_SERVER ) );

		return $_SERVER['REMOTE_ADDR'];
	}

	public function isBrowser(): bool
	{
		return \str_starts_with( $_SERVER['HTTP_ACCEPT'] ?? '', 'text/html' );
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
		$message = \preg_replace( '/^.*\\\\/', '', $ex::class ) . ': ' . $ex->getMessage();
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

	public function render( array $data, ?string $template = null, ?string $basePath = '/' ): never
	{
		if ( null === $template || \array_key_exists( 'json', $_GET ) || !$this->isBrowser() ) {
			\header( 'Content-Type: application/json' );

			exit( \json_encode( $data, \JSON_UNESCAPED_SLASHES ) . "\r\n" );
		}
		\header( 'Content-Type: text/html;charset=utf8' );

		$template = $this->getTwig()->load( "{$template}.twig" );

		exit( $template->render(
			[
				'_basePath' => $basePath,
				'_locale' => $this->getTranslationContext()->primaryLocale,
				'_supportedLocales' => $this->getTranslationContext()->getSupportedLocales(),
			] + $data,
		) );
	}

	public static function getHttpHost(): string
	{
		if ( !\array_key_exists( 'HTTP_HOST', $_SERVER ) ) {
			throw new RuntimeException( 'No HTTP Host header provided' );
		}

		return $_SERVER['HTTP_HOST'];
	}

	public static function getCurrentUrl(): string
	{
		$vhost = self::getHttpHost();

		return ( self::isHttps() ? 'https://' : 'http://' ) . $vhost . static::getCurrentPath();
	}

	public static function getCurrentIndexUrl(): string
	{
		$vhost = self::getHttpHost();

		return ( self::isHttps() ? 'https://' : 'http://' ) . $vhost . static::getCurrentIndexPath();
	}

	public function getBaseUrl(): string
	{
		$vhost = self::getHttpHost();
		$path = $this->getBasePath();

		return ( self::isHttps() ? 'https://' : 'http://' ) . $vhost . $path;
	}

	public static function isHttps(): bool
	{
		return
			( !empty( $_SERVER['HTTPS'] ) && 'off' !== $_SERVER['HTTPS'] )
			|| '443' === ( $_SERVER['SERVER_PORT'] ?? '' );
	}

	public function getTranslationContext(): TranslationContext
	{
		if ( \array_key_exists( 'lang', $_GET ) && \is_string( $_GET['lang'] ) ) {
			\setcookie( 'lang', $_GET['lang'], [
				'path' => $this->getBasePath(),
				'secure' => $this->isHttps(),
				'samesite' => $this->isHttps() ? 'None' : 'Lax', // Some browser require HTTPS for 'None'
				// https://developer.mozilla.org/en-US/docs/Web/HTTP/Cookies#controlling_third-party_cookies_with_samesite
			] );

			\header( 'Location: ' . $this->getCurrentUrl(), true, 302 );
			\header( 'Content-Type: text/plain' );
			\header( 'Cache-Control: no-store' );
			\header( 'Content-Language: en-GB' );

			exit( \implode( "\r\n", [
				"Language in cookie set to \"{$_GET['lang']}\"",
				'',
				'Please return to the previous page, or redirect:',
				'',
				$this->getCurrentUrl(),
				'',
			] ) );
		}
		if ( null === $this->translationContext ) {
			$this->translationContext = new TranslationContext(
				userLocale: $_COOKIE['lang'] ?? null,
				localeDirectory: \dirname( __DIR__, 2 ) . \DIRECTORY_SEPARATOR . 'locale',
				localeDirectoryType: 'php',
			);
		}

		return $this->translationContext;
	}

	public function getProvider(): Provider
	{
		return $this->tenantConfig->getProvider( $this->getHttpHost() );
	}

	public function getUserCredentialLog( User $user, ?Realm $realm ): UserCredentialLog
	{
		return new UserCredentialLog(
			user: $user,
			realm: $realm ?? $user->getRealm(),
			provider: $this->getProvider(),
			config: $this->config,
		);
	}

	/**
	 * Get the signer for signing profiles
	 *
	 * This is used for signing mobileconfig files, so that Apple OSes don't show a
	 * big red "not signed" warning when installing the profile.
	 *
	 * @return ?PKCS7 if the signer if configured, otherwise NULL
	 */
	public function getProfileSigner(): ?PKCS7
	{
		$dn = $this->getProvider()->profileSigner;
		if ( null === $dn ) {
			return null;
		}

		$data = $this->config->getCertificateData( $dn );
		$signingKey = $data->getString( 'key' );
		$signingCert = '';
		do {
			$signingCert .= $data->getString( 'x509' );
			$data = $this->config->getCertificateData( $data->getString( 'issuer' ) );
		} while ( $data->has( 'issuer' ) );

		return PKCS7::readChainPEM( "{$signingCert}{$signingKey}", null );
	}

	protected function getBasePath(): string
	{
		$requestUri = $_SERVER['REQUEST_URI'] ?? '';
		$path = \explode( '/', \rtrim( $this->getCurrentIndexPath(), '/' ) );
		$baseParts = \explode( '/', $this->basePath );
		while ( !empty( $baseParts ) ) {
			$element = \array_shift( $baseParts );
			if ( '..' === $element ) {
				\array_pop( $path );
			} elseif ( '.' !== $element && '' !== $element ) {
				$path[] = $element;
			}
		}

		return \implode( '/', $path ) . '/';
	}

	protected static function getCurrentIndexPath(): string
	{
		return \dirname( static::getCurrentPath() . 'x' ) . '/';
	}

	protected static function getCurrentPath(): string
	{
		$requestUri = $_SERVER['REQUEST_URI'] ?? '';

		return \strstr( $requestUri, '?', true ) ?: $requestUri;
	}

	protected function getTwig(): Environment
	{
		if ( null === $this->twig ) {
			$loader = new FilesystemLoader(
				[\implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 2 ), 'tpl'] )],
			);
			$this->twig = new Environment( $loader, [
				// 'cache' => '/path/to/compilation_cache',
			] );
			$filter = new TwigFunction(
				't',
				fn(
					\fyrkat\multilang\MultiLanguageString|string $s, mixed ...$values,
				) => \sprintf( $this->getTranslationContext()->translateHtml( $s ), ...$values ),
				['is_safe' => ['html']],
			);
			$this->twig->addFunction( $filter );
		}

		return $this->twig;
	}
}
