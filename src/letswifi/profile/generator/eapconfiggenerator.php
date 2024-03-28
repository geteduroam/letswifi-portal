<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2022, Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: 2020-2022, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\profile\generator;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use letswifi\profile\auth\AbstractAuth;
use letswifi\profile\auth\Auth;
use letswifi\profile\Helpdesk;
use letswifi\profile\Location;
use letswifi\profile\network\HS20Network;
use letswifi\profile\network\Network;
use letswifi\profile\network\SSIDNetwork;

class EapConfigGenerator extends AbstractGenerator
{
	/**
	 * Generate the eap-config profile
	 */
	public function generate(): string
	{
		$result = '<?xml version="1.0" encoding="utf-8"?>';
		$result .= ''
			. "\r\n" . '<EAPIdentityProviderList xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="eap-metadata.xsd">'
			. "\r\n\t" . '<EAPIdentityProvider ID="' . static::e( $this->profileData->getRealm() ) . '" namespace="urn:RFC4282:realm" lang="' . static::e( $this->profileData->getLanguageCode() ) . '" version="1">';
		if ( null !== $expiry = $this->getExpiry() ) {
			$expiry = new DateTimeImmutable( '@' . $expiry->getTimestamp(), new DateTimeZone( 'UTC' ) );
			$expiryString = $expiry->format( 'Y-m-d\\TH:i:s\\Z' );

			$result .= ''
				. "\r\n\t\t" . '<ValidUntil>' . static::e( $expiryString ) . '</ValidUntil>';
		}
		$result .= ''
			. "\r\n\t\t" . '<AuthenticationMethods>';
		foreach ( $this->authenticationMethods as $authentication ) {
			$result .= $this->generateAuthenticationMethodXml( $authentication );
		}
		$result .= ''
			. "\r\n\t\t" . '</AuthenticationMethods>'
			. "\r\n\t\t" . '<CredentialApplicability>';
		foreach ( $this->profileData->getNetworks() as $network ) {
			$result .= static::generateNetworkXml( $network );
		}
		$result .= ''
			. "\r\n\t\t" . '</CredentialApplicability>'
			. "\r\n\t\t" . '<ProviderInfo>'
			. "\r\n\t\t\t" . '<DisplayName>' . static::e( $this->profileData->getDisplayName() ) . '</DisplayName>';
		if ( null !== $description = $this->profileData->getDescription() ) {
			$result .= ''
				. "\r\n\t\t\t" . '<Description>' . static::e( $description ) . '</Description>';
		}
		if ( null !== $loc = $this->profileData->getProviderLocation() ) {
			$result .= ''
				. "\r\n\t\t\t" . '<ProviderLocation>' . static::generateLocationXml( $loc ) . '</ProviderLocation>';
		}
		if ( null !== $logo = $this->profileData->getProviderLogo() ) {
			$result .= ''
				. "\r\n\t\t\t" . '<ProviderLogo mime="' . static::e( $logo->getContentType() ) . '" encoding="base64">' . \base64_encode( $logo->getBytes() ) . '</ProviderLocation>';
		}
		if ( null !== $tos = $this->profileData->getTermsOfUse() ) {
			$result .= ''
				. "\r\n\t\t\t" . '<TermsOfUse>' . static::e( $tos ) . '</TermsOfUse>';
		}
		if ( null !== $helpdesk = $this->profileData->getHelpDesk() ) {
			$result .= static::generateHelpdeskXml( $helpdesk );
		}
		$result .= ''
			. "\r\n\t\t" . '</ProviderInfo>'
			. "\r\n\t" . '</EAPIdentityProvider>'
			. "\r\n" . '</EAPIdentityProviderList>'
			. "\r\n";

		return $result;
	}

	public function getExpiry(): ?DateTimeInterface
	{
		$result = null;
		foreach ( $this->authenticationMethods as $authentication ) {
			$expiry = $authentication->getExpiry();
			if ( null !== $expiry ) {
				if ( null === $result || $result->getTimestamp() > $expiry->getTimestamp() ) {
					$result = $expiry;
				}
			}
		}

		return $result;
	}

	public function getContentType(): string
	{
		// There is no reference to this, and no official registration,
		// but CAT uses application/eap-config.
		// Unregistered content types should use the x- prefix though.
		return 'application/x-eap-config';
	}

	public function getFileExtension(): string
	{
		return 'eap-config';
	}

	private static function generateNetworkXml( Network $network ): string
	{
		if ( $network instanceof HS20Network ) {
			return static::generateHS20NetworkXml( $network );
		}
		if ( $network instanceof SSIDNetwork ) {
			return static::generateSSIDNetworkXml( $network );
		}
		throw new InvalidArgumentException( 'Unsupported network: ' . \get_class( $network ) );
	}

	private static function generateHS20NetworkXml( HS20Network $network ): string
	{
		return ''
			. "\r\n\t\t\t" . '<IEEE80211>'
			. "\r\n\t\t\t\t" . '<ConsortiumOID>' . static::e( $network->getConsortiumOID() ) . '</ConsortiumOID>'
			. "\r\n\t\t\t" . '</IEEE80211>';
	}

	private static function generateSSIDNetworkXml( SSIDNetwork $network ): string
	{
		return ''
			. "\r\n\t\t\t" . '<IEEE80211>'
			. "\r\n\t\t\t\t" . '<SSID>' . static::e( $network->getSsid() ) . '</SSID>'
			. "\r\n\t\t\t\t" . '<MinRSNProto>' . static::e( $network->getMinRSNProto() ) . '</MinRSNProto>'
			. "\r\n\t\t\t" . '</IEEE80211>';
	}

	private function generateAuthenticationMethodXml( Auth $authenticationMethod ): string
	{
		if ( $authenticationMethod instanceof \letswifi\profile\auth\TlsAuth ) {
			return $this->generateTlsAuthenticationMethodXml( $authenticationMethod );
		}

		throw new InvalidArgumentException( 'Unsupported authentication method: ' . \get_class( $authenticationMethod ) );
	}

	/**
	 * Generate EAP config data for EAP-TLS authentication
	 *
	 * @param \letswifi\profile\auth\TlsAuth $authenticationMethod The authentication method to be converted to XML
	 *
	 * @return string XML portion for wifi and certificates, to be used in a EAP config file
	 */
	private function generateTlsAuthenticationMethodXml( \letswifi\profile\auth\TlsAuth $authenticationMethod ): string
	{
		$identity = $authenticationMethod->getIdentity();
		$pkcs12 = $authenticationMethod->getPKCS12();
		$defaultPassphrase = 'pkcs12';

		if ( null !== $pkcs12 ) {
			// We need 3DES support, since some of our supported clients support nothing else
			$pkcs12 = $pkcs12->use3des();
		}

		$result = '';
		$result .= ''
			. "\r\n\t\t\t" . '<AuthenticationMethod>'
			. "\r\n\t\t\t\t" . '<EAPMethod>'
			. "\r\n\t\t\t\t\t" . '<Type>13</Type>'
			. "\r\n\t\t\t\t" . '</EAPMethod>'
			. "\r\n\t\t\t\t" . '<ServerSideCredential>';
		foreach ( $authenticationMethod->getServerCACertificates() as $ca ) {
			$result .= ''
				. "\r\n\t\t\t\t\t" . '<CA format="X.509" encoding="base64">' . AbstractAuth::pemToBase64Der( $ca->getX509Pem() ) . '</CA>';
		}
		foreach ( $authenticationMethod->getServerNames() as $serverName ) {
			$result .= ''
				. "\r\n\t\t\t\t\t" . '<ServerID>' . static::e( $serverName ) . '</ServerID>';
		}
		$result .= ''
			. "\r\n\t\t\t\t" . '</ServerSideCredential>';
		if ( null === $authenticationMethod->getPKCS12() ) {
			$result .= ''
				. "\r\n\t\t\t\t" . '<ClientSideCredential/>';
		} else {
			$result .= ''
				. "\r\n\t\t\t\t" . '<ClientSideCredential>';
			if ( null !== $identity ) {
				// https://github.com/GEANT/CAT/blob/v2.0.3/devices/xml/eap-metadata.xsd
				// The schema specifies <OuterIdentity>
				// https://tools.ietf.org/html/draft-winter-opsawg-eap-metadata-02
				// Expired draft specifices <AnonymousIdentity>
				// cat.eduroam.org uses <OuterIdentity>, so we do too
				$result .= ''
					. "\r\n\t\t\t\t\t" . '<OuterIdentity>' . static::e( $identity ) . '</OuterIdentity>';
			}
			if ( null !== $pkcs12 ) {
				$result .= ''
					. "\r\n\t\t\t\t" . '<ClientCertificate format="PKCS12" encoding="base64">' . \base64_encode( $pkcs12->getPKCS12Bytes( $this->passphrase ?: $defaultPassphrase ) ) . '</ClientCertificate>';
				if ( !$this->passphrase ) {
					$result .= ''
						. "\r\n\t\t\t\t" . '<Passphrase>' . static::e( $defaultPassphrase ) . '</Passphrase>';
				}
				$result .= ''
					. "\r\n\t\t\t\t" . '</ClientSideCredential>';
			}
		}
		$result .= ''
				. "\r\n\t\t\t" . '</AuthenticationMethod>';

		return $result;
	}

	private static function generateHelpdeskXml( Helpdesk $helpdesk ): string
	{
		$mail = $helpdesk->getMail();
		$web = $helpdesk->getWeb();
		$phone = $helpdesk->getPhone();
		$result = "\r\n\t\t\t<Helpdesk>";
		if ( null !== $mail ) {
			$result .= "\r\n\t\t\t\t<EmailAddress>" . static::e( $mail ) . '</EmailAddress>';
		}
		if ( null !== $web ) {
			$result .= "\r\n\t\t\t\t<WebAddress>" . static::e( $web ) . '</WebAddress>';
		}
		if ( null !== $phone ) {
			$result .= "\r\n\t\t\t\t<Phone>" . static::e( $phone ) . '</Phone>';
		}
		$result .= "\r\n\t\t\t</Helpdesk>";

		return $result;
	}

	private static function generateLocationXml( Location $location ): string
	{
		$lat = $location->getLatitude();
		$lon = $location->getLongitude();

		return ''
			. "\r\n<ProviderLocation>"
			. "\r\n<Latitude>{$lat}</Latitude>"
			. "\r\n<Longitude>{$lon}</Longitude>"
			. "\r\n</ProviderLocation>";
	}
}
