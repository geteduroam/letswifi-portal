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
	/**
	 * Amount of iterations, must be between 20000 (according to documentation)
	 * and 500000 (according to source)
	 *
	 * @see https://github.com/chromium/chromium/blob/97efa54eb2/components/onc/docs/onc_spec.md#encryptedconfiguration-type
	 * @see https://github.com/chromium/chromium/blob/97efa54eb2/chromeos/components/onc/onc_utils.cc#L386
	 */
	public const PBKDF2_ITERATIONS = 20000;

	/**
	 * Generate the onc profile
	 *
	 * @return string JSON ONC payload
	 */
	public function generate(): string
	{
		$payload = $this->generatePayload();

		if ( $this->passphrase ) {
			$payload = $this->encrypt( $payload, $this->passphrase );
		}

		$payload = \json_encode(
				$payload,
				\JSON_UNESCAPED_SLASHES | \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR,
			) . "\n";

		return static::spacesToTabs( $payload );
	}

	/**
	 * @see https://github.com/chromium/chromium/blob/97efa54eb2/components/onc/docs/onc_spec.md#detection
	 */
	public function getContentType(): string
	{
		return 'application/x-onc';
	}

	/**
	 * @see https://github.com/chromium/chromium/blob/97efa54eb2/components/onc/docs/onc_spec.md#detection
	 */
	public function getFileExtension(): string
	{
		return 'onc';
	}

	/**
	 * Encrypt ONC configuration
	 *
	 * @param array  $unencryptedONC UnencryptedConfiguration ONC, unserialized
	 * @param string $passphrase     Passphrase to encrypt the configuration with
	 *
	 * @return array{Cipher:'AES256',Ciphertext:string,HMAC:string,HMACMethod:'SHA1',Salt:string,Stretch:'PBKDF2',Iterations:int,IV:string,Type:'EncryptedConfiguration'} EncryptedConfiguration ONC, unserialized
	 *
	 * @see https://github.com/chromium/chromium/blob/97efa54eb2/chromeos/components/onc/onc_utils.cc#L383-L479
	 * @see https://github.com/chromium/chromium/blob/97efa54eb2/components/onc/docs/onc_spec.md#encryptedconfiguration-type
	 * @see https://github.com/chromium/chromium/blob/97efa54eb2/components/onc/docs/onc_spec.md#encrypted-format-example
	 */
	protected static function encrypt( array $unencryptedONC, string $passphrase ): array
	{
		if ( !\array_key_exists( 'Type', $unencryptedONC ) ) {
			throw new InvalidArgumentException( 'ONC payload to be encrypted is not a valid ONC' );
		}
		if ( 'UnencryptedConfiguration' !== $unencryptedONC['Type']) {
			throw new InvalidArgumentException( "Can only encrypt UnencryptedConfiguration, got '{$unencryptedONC['Type']}'" );
		}

		$clearText = \json_encode(
			$unencryptedONC,
			\JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR,
		);
		/** @psalm-suppress RedundantConditionGivenDocblockType phan is not so sure about json_encode not returning false */
		\assert( \is_string( $clearText ), 'json_encode can only generate string when JSON_THROW_ON_ERROR is set' );

		$salt = \random_bytes( 8 );
		$key = \hash_pbkdf2( 'sha1', $passphrase, $salt, self::PBKDF2_ITERATIONS, 32, true );
		$iv = \random_bytes( 16 );
		$cipherText = \openssl_encrypt( $clearText, 'AES-256-CBC', $key, \OPENSSL_RAW_DATA, $iv );
		if ( false === $cipherText ) {
			throw new RuntimeException( 'Unable to encrypt profile' );
		}
		$hmac = \hash_hmac( 'sha1', $cipherText, $key, true );

		return [
			'Cipher' => 'AES256',
			'Ciphertext' => \base64_encode( $cipherText ),
			'HMAC' => \base64_encode( $hmac ),
			'HMACMethod' => 'SHA1',
			'Salt' => \base64_encode( $salt ),
			'Stretch' => 'PBKDF2',
			'Iterations' => self::PBKDF2_ITERATIONS,
			'IV' => \base64_encode( $iv ),
			'Type' => 'EncryptedConfiguration',
		];
	}

	/**
	 * Generate ONC configuration with network and certificate payload
	 *
	 * @return array{Type:'UnencryptedConfiguration',Certificates:list<array{GUID:string,Remove:false,Type:string,X509?:string,PKCS12?:string}>,NetworkConfigurations:list<mixed>} Unencrypted ONC data structure with certificates and network configuration
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
		\assert( null !== $pkcs12 ); // we already checked this when we created $tlsAuthMethods
		// We need 3DES support, since some of our supported clients support nothing else
		$pkcs12 = $pkcs12->use3des();

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
			'Certificates' => \array_values( \array_merge( $caCertificates, [$clientCertificate] ) ),
			'NetworkConfigurations' => \array_values( $networkConfigurations ),
		];
	}

	/**
	 * @param Network       $network
	 * @param string        $clientCertID       ID of client certificate
	 * @param string        $clientCertCN       Common name of client certificate
	 * @param array<string> $caIDs              IDs of server CA certificates
	 * @param string        $serverSubjectMatch Substring certificate subject name must match
	 *
	 * @return ?array ONC NetworkConfiguration struct
	 */
	protected static function generateNetworkConfiguration( Network $network, string $clientCertID, string $clientCertCN, array $caIDs, string $serverSubjectMatch ): ?array
	{
		if ( !($network instanceof SSIDNetwork ) ) {
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
					'ServerCARefs' => \array_values( $caIDs ),
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
	 * @param TlsAuth $authMethod Authentication method
	 *
	 * @return array<array{GUID: string, Remove: false, Type: string, X509: string}> ONC Certificate payload
	 */
	protected static function getCAPayloadFromAuthMethod( TlsAuth $authMethod ): array
	{
		return \array_map( static function ( X509 $x509 ): array {
			$uuid = static::uuidgen();

			return [
				'GUID' => "\\{{$uuid}\\}",
				'Remove' => false,
				'Type' => 'Authority',
				'X509' => \base64_encode( $x509->getX509Der() ),
			];
		}, $authMethod->getServerCACertificates() );
	}

	/**
	 * @param PKCS12 $pkcs12 Client certificate
	 *
	 * @return array{GUID: string, Remove: false, Type: string, PKCS12: string} ONC Certificate payload
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

	/**
	 * Convert spaces to tabs
	 *
	 * @param $document Document to convert
	 * @param $indentSize Amount of spaces used for indentation in document
	 *
	 * @return string Document indented with tabs
	 */
	private static function spacesToTabs( string $document, int $indentSize = 4 ): string
	{
		return \preg_replace_callback(
			'/^\s+/m',
			static function ( array $match ) use ( $indentSize ): string {
				return \str_replace( \str_repeat( ' ', $indentSize ), "\t", $match[0] );
			},
			$document,
		);
	}
}
