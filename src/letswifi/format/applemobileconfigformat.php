<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\format;

use InvalidArgumentException;
use letswifi\credential\CertificateCredential;
use letswifi\provider\NetworkPasspoint;
use letswifi\provider\NetworkSSID;

class AppleMobileconfigFormat extends Format
{
	/**
	 * Generate the eap-config profile
	 */
	public function generate(): string
	{
		$uuid = static::uuidgen();
		$identifier = $this->getIdentifier();
		\assert( $this->credential instanceof CertificateCredential ); // We don't support anything else yet

		$tlsAuthMethodUuid = static::uuidgen();
		$defaultPassphrase = 'pkcs12';

		/** @var array<\fyrkat\openssl\X509> */
		$caCertificates = $this->credential->realm->trust;

		// If we include the CA, MacOS will also trust that CA for HTTPS traffic
		// MacOS needs the bundle to be 3DES encoded
		$pkcs12 = $this->credential->getPKCS12( ca: false, des: true );

		$result = '<?xml version="1.0" encoding="UTF-8"?>'
			. "\n" . '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">'
			. "\n" . '<plist version="1.0">'
			. "\n<dict>"
			. "\n	<key>PayloadDisplayName</key>"
			. "\n	<string>" . static::e( $this->credential->realm->displayName ) . '</string>'
			. "\n	<key>PayloadIdentifier</key>"
			. "\n	<string>" . static::e( $identifier ) . '</string>'
			. "\n	<key>PayloadUUID</key>"
			. "\n	<string>" . static::e( $uuid ) . '</string>'
			. "\n	<key>PayloadRemovalDisallowed</key>"
			. "\n	<false/>"
			. "\n	<key>PayloadType</key>"
			. "\n	<string>Configuration</string>"
			. "\n	<key>PayloadVersion</key>"
			. "\n	<integer>1</integer>"
			. "\n";
		if ( null !== $description = $this->credential->realm->description ) {
			$result .= '	<key>PayloadDescription</key>'
				. "\n	<string>" . static::e( $description ) . '</string>'
				. "\n";
		}
		if ( null !== $expiry = $this->credential->getExpiry() ) {
			$expiryString = \gmdate( 'Y-m-d\\TH:i:s\\Z', $expiry->getTimestamp() );
			$result .= '	<key>RemovalDate</key>'
					. "\n	<date>" . static::e( $expiryString ) . '</date>'
					. "\n";
		}
		$result .= '	<key>PayloadContent</key>'
			. "\n	<array>"
			. "\n		<dict>"
			. "\n";
		if ( !$this->passphrase ) {
			$result .= '			<key>Password</key>'
				. "\n			<string>" . static::e( $defaultPassphrase ) . '</string>'
				. "\n";
		}
		$result .= '			<key>PayloadUUID</key>'
			. "\n			<string>" . static::e( $tlsAuthMethodUuid ) . '</string>'
			. "\n			<key>PayloadIdentifier</key>"
			. "\n			<string>" . static::e( $identifier . '.' . $tlsAuthMethodUuid ) . '</string>'
			. "\n			<key>PayloadCertificateFileName</key>"
			. "\n			<string>" . static::e( $pkcs12->x509->getSubject()->getCommonName() ) . '.p12</string>'
			. "\n			<key>PayloadDisplayName</key>"
			. "\n			<string>" . static::e( $pkcs12->x509->getSubject()->getCommonName() ) . '</string>'
			. "\n			<key>PayloadContent</key>"
			. "\n			<data>"
			. "\n				" . static::e( static::columnFormat( \base64_encode( $pkcs12->getPKCS12Bytes( $this->passphrase ?: $defaultPassphrase ) ), 52, 4 ) )
			. "\n			</data>"
			. "\n			<key>PayloadType</key>"
			. "\n			<string>com.apple.security.pkcs12</string>"
			. "\n			<key>PayloadVersion</key>"
			. "\n			<integer>1</integer>"
			. "\n		</dict>"
			. "\n";

		$uuids = \array_map(
			static fn ( $_ ) => static::uuidgen(),
			\array_fill( 0, \count( $caCertificates ), null ),
		);

		/** @var array<string,\fyrkat\openssl\X509> */
		$caCertificates = \array_combine( $uuids, $caCertificates );
		foreach ( $caCertificates as $uuid => $ca ) {
			$result .= ''
				. "\n		<dict>"
				. "\n			<key>PayloadCertificateFileName</key>"
				. "\n			<string>" . static::e( $ca->getSubject()->getCommonName() ) . '.cer</string>'
				. "\n			<key>PayloadContent</key>"
				. "\n			<data>"
				. "\n				" . static::columnFormat( \base64_encode( $ca->getX509Der() ), indentation: 4 )
				. "\n			</data>"
				. "\n			<key>PayloadDisplayName</key>"
				. "\n			<string>" . static::e( $ca->getSubject()->getCommonName() ) . '</string>'
				. "\n			<key>PayloadIdentifier</key>"
				. "\n			<string>" . static::e( $identifier . '.' . $uuid ) . '</string>'
				. "\n			<key>PayloadType</key>"
				. "\n			<string>com.apple.security.root</string>"
				. "\n			<key>PayloadUUID</key>"
				. "\n			<string>" . static::e( $uuid ) . '</string>'
				. "\n			<key>PayloadVersion</key>"
				. "\n			<integer>1</integer>"
				. "\n		</dict>"
				. "\n";
		}
		$payloadNetworkCount = 0;
		foreach ( $this->credential->realm->networks as $network ) {
			if ( $network instanceof NetworkSSID || $network instanceof NetworkPasspoint ) {
				// TODO assumes TLSAuth, it's the only option currently
				$result .= '		<dict>'
					. "\n			<key>AutoJoin</key>"
					. "\n			<true/>"
					. "\n			<key>EAPClientConfiguration</key>"
					. "\n			<dict>"
					. "\n				<key>AcceptEAPTypes</key>"
					. "\n				<array>"
					. "\n					<integer>13</integer>"
					. "\n				</array>"
					. "\n				<key>EAPFASTProvisionPAC</key>"
					. "\n				<false/>"
					. "\n				<key>EAPFASTProvisionPACAnonymously</key>"
					. "\n				<false/>"
					. "\n				<key>EAPFASTUsePAC</key>"
					. "\n				<false/>"
					. "\n				<key>PayloadCertificateAnchorUUID</key>"
					. "\n				<array>"
					. "\n";
				foreach ( $caCertificates as $uuid => $_ ) {
					$result .= '					<string>' . static::e( $uuid ) . '</string>'
						. "\n";
				}
				$result .= '				</array>'
					. "\n				<key>TLSTrustedServerNames</key>"
					. "\n				<array>"
					. "\n";
				foreach ( $this->credential->realm->serverNames as $serverName ) {
					$result .= '					<string>' . static::e( $serverName ) . '</string>'
						. "\n";
				}
				$payloadDisplayName = $network->displayName;
				$result .= '				</array>'
					. "\n			</dict>"
					. "\n			<key>EncryptionType</key>"
					. "\n			<string>WPA</string>"
					. "\n			<key>HIDDEN_NETWORK</key>"
					. "\n			<false/>"
					. "\n			<key>PayloadCertificateUUID</key>"
					. "\n			<string>" . static::e( $tlsAuthMethodUuid ) . '</string>'
					. "\n			<key>PayloadDisplayName</key>"
					. "\n			<string>Wi-Fi (" . static::e( $payloadDisplayName ) . ')</string>'
					. "\n			<key>PayloadIdentifier</key>"
					. "\n			<string>" . static::e( $identifier ) . '.wifi.' . $payloadNetworkCount . '</string>'
					. "\n			<key>PayloadType</key>"
					. "\n			<string>com.apple.wifi.managed</string>"
					. "\n			<key>PayloadUUID</key>"
					. "\n			<string>" . static::uuidgen() . '</string>'
					. "\n			<key>PayloadVersion</key>"
					. "\n			<integer>1</integer>"
					. "\n			<key>ProxyType</key>"
					. "\n			<string>None</string>"
					. "\n";
			}
			if ( $network instanceof NetworkSSID ) {
				$result .= '			<key>SSID_STR</key>'
					. "\n			<string>" . static::e( $network->ssid ) . '</string>'
					. "\n		</dict>"
					. "\n";
			} elseif ( $network instanceof NetworkPasspoint ) {
				$result .= '			<key>IsHotspot</key>'
					. "\n			<true/>"
					. "\n			<key>ServiceProviderRoamingEnabled</key>"
					. "\n			<true/>"
					. "\n			<key>DisplayedOperatorName</key>"
					. "\n			<string>" . static::e( $this->credential->realm->displayName ) . ' via Passpoint</string>'
					. "\n			<key>DomainName</key>"
					. "\n			<string>" . static::e( $this->credential->realm->realmId ) . '</string>'
					. "\n			<key>RoamingConsortiumOIs</key>"
					. "\n			<array>"
					. "\n";
				foreach ( $network->oids as $oid ) {
					$result .= '				<string>' . \strtoupper( static::e( $oid ) ) . '</string>'
						. "\n";
				}
				$result .= '			</array>'
					. "\n";
				if ( $network->naiRealms ) {
					$result .= '			<key>NAIRealmNames</key>'
						. "\n			<array>"
						. "\n";
					foreach ( $network->naiRealms as $nai ) {
						$result .= '				<string>' . static::e( $nai ) . '</string>'
						. "\n";
					}
					$result .= '			</array>'
					. "\n";
				}
				$result .= '			<key>_UsingHotspot20</key>'
				. "\n			<true/>"
				. "\n			<key>IsHotspot</key>"
				. "\n			<true/>"
				. "\n		</dict>"
				. "\n";
			} else {
				throw new InvalidArgumentException( 'Only SSID or Hotspot 2.0 networks are supported, got ' . $network::class );
			}
			++$payloadNetworkCount;
		}
		$result .= '	</array>'
			. "\n</dict>"
			. "\n</plist>"
			. "\n";

		if ( $this->profileSigner ) {
			$result = $this->profileSigner->sign( $result );
		}

		return $result;
	}

	public function getFileExtension(): string
	{
		return 'mobileconfig';
	}

	public function getContentType(): string
	{
		return 'application/x-apple-aspen-config';
	}
}
