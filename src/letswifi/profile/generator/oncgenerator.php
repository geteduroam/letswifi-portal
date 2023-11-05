<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2022, Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: 2020-2022, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\profile\generator;

use fyrkat\openssl\PKCS12;
use fyrkat\openssl\X509;

use InvalidArgumentException;

use letswifi\profile\auth\TlsAuth;
use letswifi\profile\network\Network;
use letswifi\profile\network\SSIDNetwork;

use RuntimeException;

class ONCGenerator extends AbstractGenerator
{
	public const PBKDF2_ITERATIONS = 20000;

	/**
	 * Generate the onc profile
	 */
	public function generate(): string
	{
		$payload = $this->generatePayload();
		if ($this->passphrase) {
			$payload = \json_encode(
				$payload,
				\JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR,
			);
			$payload = $this->encrypt( $payload, $this->passphrase );
		}

		return \json_encode(
			$payload,
			\JSON_UNESCAPED_SLASHES | \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR,
		) . "\n";
	}

	public function getContentType(): string
	{
		return 'application/x-onc';
	}

	public function getFileExtension(): string
	{
		return 'onc';
	}

	protected static function encrypt( string $clearText, string $passphrase ): array
	{
		// TODO see if hmac can use something stronger than SHA1

		$salt = \random_bytes( 12 );
		$key = \hash_pbkdf2('sha1', $passphrase, $salt, self::PBKDF2_ITERATIONS, 32, true);
		$iv = \random_bytes(16);
		$cipherText = \openssl_encrypt($clearText, 'AES-256-CBC', $key, \OPENSSL_RAW_DATA, $iv);
		if ( false === $cipherText ) {
			throw new RuntimeException( 'Unable to encrypt profile' );
		}
		$hmac = \hash_hmac('sha1', $cipherText, $key, true);

		return [
			'Cipher' => 'AES256',
			'Ciphertext' => \base64_encode( $cipherText ),
			'HMAC' => \base64_encode($hmac),
			'HMACMethod' => 'SHA1',
			'Salt' => \base64_encode($salt),
			'Stretch' => 'PBKDF2',
			'Iterations' => self::PBKDF2_ITERATIONS,
			'IV' => \base64_encode($iv),
			'Type' => 'EncryptedConfiguration',
		];
	}

	/**
	 * @return array
	 */
	protected function generatePayload(): array
	{
		$tlsAuthMethods = \array_filter(
				$this->authenticationMethods,
				static function ($a): bool { return $a instanceof TlsAuth && null !== $a->getPKCS12(); },
			);
		if ( 1 !== \count( $tlsAuthMethods ) ) {
			throw new InvalidArgumentException( 'Expected 1 TLS auth method, got ' . \count( $tlsAuthMethods ) );
		}
		$tlsAuthMethod = \reset( $tlsAuthMethods );
		/** @psalm-suppress RedundantCondition */
		\assert( $tlsAuthMethod instanceof TlsAuth );
		$pkcs12 = $tlsAuthMethod->getPKCS12();
		\assert( null !== $pkcs12 );

		$caCertificates = $this->getCAPayloadFromAuthMethod( $tlsAuthMethod );
		$clientCertificate = $this->getClientCredentialPayloadFromAuthMethod( $pkcs12 );

		$caIDs = \array_map( static function ( $certData ){return $certData['GUID']; }, $caCertificates );
		$clientCertID = $clientCertificate['GUID'];
		$serverNames = $tlsAuthMethod->getServerNames();
		$serverSubjectMatch = $this->getLongestSuffix( ...$serverNames );

		$clientCertCN = $pkcs12->getX509()->getSubject()->getCommonName();

		$networkConfigurations = \array_filter( \array_map( function ( Network $network ) use ($clientCertID, $clientCertCN, $caIDs, $serverSubjectMatch) {
			return $this->generateNetworkConfiguration( $network, $clientCertID, $clientCertCN, $caIDs, $serverSubjectMatch );
		}, $this->profileData->getNetworks() ) );

		return [
			'Type' => 'UnencryptedConfiguration',
			'Certificates' => \array_merge( $caCertificates, [$clientCertificate] ),
			'NetworkConfigurations' => $networkConfigurations,
		];
	}

	/**
	 * @param array<string> $caIDs
	 *
	 * @return ?array
	 */
	protected static function generateNetworkConfiguration( Network $network, string $clientCertID, string $clientCertCN, array $caIDs, string $serverSubjectMatch ): ?array
	{
		if (!($network instanceof SSIDNetwork )) {
			return null;
		}

		$uuid = static::uuidgen();

		return [
			'GUID' => $uuid,
			'Name' => 'eduroam',
			'ProxySettings' => [
				'Type' => 'WPAD',
			],
			'Remove' => false,
			'Type' => 'WiFi',
			'WiFi' => [
				'AutoConnect' => true,
				'EAP' => [
					'ClientCertRef' => $clientCertID,
					'ClientCertType' => 'Ref',
					'Identity' => $clientCertCN,
					'Outer' => 'EAP-TLS',
					'SaveCredentials' => true,
					'ServerCARefs' => $caIDs,
					'SubjectMatch' => $serverSubjectMatch,
					'UseSystemCAs' => false,
				],
				'HiddenSSID' => false,
				'SSID' => $network->getSSID(),
				'Security' => 'WPA-EAP',
			],
		];
	}

	/**
	 * @return array<array{GUID: string, Remove: false, Type: string, X509: string}>
	 */
	protected static function getCAPayloadFromAuthMethod( TlsAuth $authMethod ): array
	{
		return \array_map( static function (X509 $x509 ): array {
			$uuid = static::uuidgen();

			// writing as "\{$uuid\}" makes php-cs-fixer crash
			return [
				'GUID' => '{' . $uuid . '}',
				'Remove' => false,
				'Type' => 'Authority',
				'X509' => \base64_encode( $x509->getX509Der() ),
			];
		}, $authMethod->getServerCACertificates() );
	}

	/**
	 * @return array{GUID: string, Remove: false, Type: string, PKCS12: string}
	 */
	protected static function getClientCredentialPayloadFromAuthMethod( PKCS12 $pkcs12 ): array
	{
		// We use a PKCS12 without passphrase here.
		// If $this->passphrase was set, we'll use that to encrypt the whole payload instead
		$uuid = static::uuidgen();

		return [
			'GUID' => "[${uuid}]",
			'Remove' => false,
			'Type' => 'Client',
			'PKCS12' => \base64_encode( $pkcs12->getPKCS12Bytes( '' ) ),
		];
	}

	/**
	 * Get the longest common suffix domain components from a list of hostnames
	 *
	 * @param string $hostnames A list of host names
	 *
	 * @return string The longest common suffix for all given host names
	 */
	protected static function getLongestSuffix( string ...$hostnames ): string
	{
		if ( empty( $hostnames ) ) {
			return '';
		}
		if ( \count( $hostnames ) === 1) {
			return \reset( $hostnames );
		}
		$longest = $hostnames[0];
		foreach ( $hostnames as $candidate ) {
			$pos = \strlen( $candidate );
			do {
				$pos = (int)\strrpos( $candidate, '.', -1 * \strlen( $candidate ) + $pos - 1 );
				echo "'${longest}' ends with " . \substr( $candidate, $pos ) . "?\n";
			} while ( 0 < $pos && \str_ends_with( $longest, (string)\substr( $candidate, $pos ) ) );
			if ( !\str_ends_with( $longest, (string)\substr( $candidate, $pos ) ) ) {
				$pos = \strpos( $candidate, '.', $pos + 1 );
			}
			if ( false === $pos ) {
				$longest = '';
				break;
			}
			if ( \str_ends_with( $longest, (string)\substr( $candidate, $pos ) ) ) {
				$longest = (string)\substr( $candidate, 0 === $pos ? 0 : $pos + 1 );
			}
		}

		return $longest;
	}
}
