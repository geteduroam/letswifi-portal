<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2020, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\profile\auth;

use DateTimeInterface;

use fyrkat\openssl\PKCS12;
use fyrkat\openssl\X509;

class TlsAuth extends AbstractAuth
{
	/** @var ?PKCS12 */
	private $pkcs12;

	/** @var string */
	private $passphrase;

	/** @var ?string */
	private $identity;

	/**
	 * @param array<X509>   $caCertificates Trusted CA certificates
	 * @param array<string> $serverNames    Accepted server names
	 * @param ?string       $identity       Anonymous identity
	 * @param ?PKCS12       $pkcs12         Certificate for user/device authentication
	 * @param ?string       $passphrase     Transient password to be used for encrypting the PKCS12 payload
	 */
	public function __construct( array $caCertificates, array $serverNames, ?string $identity, ?PKCS12 $pkcs12, ?string $passphrase = null )
	{
		parent::__construct( $caCertificates, $serverNames );
		$this->identity = $identity;
		$this->pkcs12 = $pkcs12;
		$this->passphrase = $passphrase ?? 'pkcs12';
	}

	/**
	 * Generate EAP config data for EAP-TLS authentication
	 *
	 * @return string XML portion for wifi and certificates, to be used in a EAP config file
	 */
	public function generateEapConfigXml(): string
	{
		$result = '';
		$result .= ''
			. "\r\n\t\t\t" . '<AuthenticationMethod>'
			. "\r\n\t\t\t\t" . '<EAPMethod>'
			. "\r\n\t\t\t\t\t" . '<Type>13</Type>'
			. "\r\n\t\t\t\t" . '</EAPMethod>'
			. "\r\n\t\t\t\t" . '<ServerSideCredential>'
			;
		foreach ( $this->getServerCACertificates() as $ca ) {
			$result .= ''
				. "\r\n\t\t\t\t\t" . '<CA format="X.509" encoding="base64">' . static::pemToBase64Der( $ca->getX509Pem() ) . '</CA>'
				;
		}
		foreach ( $this->getServerNames() as $serverName ) {
			$result .= ''
				. "\r\n\t\t\t\t\t" . '<ServerID>' . static::e( $serverName ) . '</ServerID>'
				;
		}
		$result .= ''
			. "\r\n\t\t\t\t" . '</ServerSideCredential>'
			;
		if ( null === $this->pkcs12 ) {
			$result .= ''
				. "\r\n\t\t\t\t" . '<ClientSideCredential/>'
				;
		} else {
			$result .= ''
				. "\r\n\t\t\t\t" . '<ClientSideCredential>'
				;
			if ( null !== $this->identity ) {
				// https://github.com/GEANT/CAT/blob/v2.0.3/devices/xml/eap-metadata.xsd
				// The schema specifies <OuterIdentity>
				// https://tools.ietf.org/html/draft-winter-opsawg-eap-metadata-02
				// Expired draft specifices <AnonymousIdentity>
				// cat.eduroam.org uses <OuterIdentity>, so we do too
				$result .= ''
					. "\r\n\t\t\t\t\t" . '<OuterIdentity>' . static::e( $this->identity ) . '</OuterIdentity>'
					;
			}
			$result .= ''
				. "\r\n\t\t\t\t" . '<ClientCertificate format="PKCS12" encoding="base64">' . \base64_encode( $this->pkcs12->getPKCS12Bytes( $this->passphrase ) ) . '</ClientCertificate>'
				. "\r\n\t\t\t\t" . '<Passphrase>' . static::e( $this->passphrase ) . '</Passphrase>'
				. "\r\n\t\t\t\t" . '</ClientSideCredential>'
				;
		}
		$result .= ''
				. "\r\n\t\t\t" . '</AuthenticationMethod>'
				;

		return $result;
	}

	public function getExpiry(): ?DateTimeInterface
	{
		return null === $this->pkcs12 ? null : $this->pkcs12->getX509()->getValidTo();
	}

	private static function e( string $s ): string
	{
		return \htmlspecialchars( $s, \ENT_QUOTES, 'UTF-8' );
	}
}
