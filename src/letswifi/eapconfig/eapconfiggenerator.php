<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2020, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\EapConfig;

use DateTimeInterface;

use letswifi\EapConfig\Auth\IAuthenticationMethod;
use letswifi\EapConfig\Profile\IProfileData;

class EapConfigGenerator
{
	/**
	 * Data about the institution
	 *
	 * @var IProfileData
	 */
	protected $profileData;

	/**
	 * Possible authentication methods
	 *
	 * @var array<IAuthenticationMethod>
	 */
	protected $authenticationMethods;

	/**
	 * Create a new generator.
	 *
	 * @param IProfileData                 $profileData           Profile data
	 * @param array<IAuthenticationMethod> $authenticationMethods Authentication methods
	 */
	public function __construct( IProfileData $profileData, array $authenticationMethods )
	{
		$this->profileData = $profileData;
		$this->authenticationMethods = $authenticationMethods;
	}

	/**
	 * Generate the eap-config profile
	 */
	public function generate(): string
	{
		$result = '<?xml version="1.0" encoding="utf-8"?>';
		$result .= ''
			. "\r\n" . '<EAPIdentityProviderList xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="eap-metadata.xsd">'
			. "\r\n\t" . '<EAPIdentityProvider ID="' . static::e( $this->profileData->getRealm() ) . '" namespace="urn:RFC4282:realm" lang="' . static::e( $this->profileData->getLanguageCode() ) . '" version="1">'
			;
		if ( null !== $expiry = $this->getExpiry() ) {
			$result .= ''
				. "\r\n\t\t" . '<ValidUntil>' . $expiry->format( 'Y-m-d\\TH:i:s' ) . '</ValidUntil>'
				;
		}
		$result .= ''
			. "\r\n\t\t" . '<AuthenticationMethods>'
			;
		foreach ( $this->authenticationMethods as $authentication ) {
			$result .= $authentication->generateEapConfigXml();
		}
		$result .= ''
			. "\r\n\t\t" . '</AuthenticationMethods>'
			. "\r\n\t\t" . '<CredentialApplicability>'
			;
		foreach ( $this->profileData->getCredentialApplicabilities() as $credentialApplicability ) {
			$result .= $credentialApplicability->generateEapConfigXml();
		}
		$result .= ''
			. "\r\n\t\t" . '</CredentialApplicability>'
			. "\r\n\t\t" . '<ProviderInfo>'
			. "\r\n\t\t\t" . '<DisplayName>' . static::e( $this->profileData->getDisplayName() ) . '</DisplayName>'
			;
		if ( null !== $description = $this->profileData->getDescription() ) {
			$result .= ''
				. "\r\n\t\t\t" . '<Description>' . static::e( $description ) . '</Description>'
				;
		}
		if ( null !== $loc = $this->profileData->getProviderLocation() ) {
			$result .= ''
				. "\r\n\t\t\t" . '<ProviderLocation>' . $loc->generateEapConfigXml() . '</ProviderLocation>'
				;
		}
		if ( null !== $logo = $this->profileData->getProviderLogo() ) {
			$result .= ''
				. "\r\n\t\t\t" . '<ProviderLogo mime="' . static::e( $logo->getContentType() ) . '" encoding="base64">' . \base64_encode( $logo->getBytes() ) . '</ProviderLocation>'
				;
		}
		if ( null !== $tos = $this->profileData->getTermsOfUse() ) {
			$result .= ''
				. "\r\n\t\t\t" . '<TermsOfUse>' . static::e( $tos ) . '</TermsOfUse>'
				;
		}
		if ( null !== $helpdesk = $this->profileData->getHelpDesk() ) {
			$result .= $helpdesk->generateEapConfigXml();
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

	private static function e( string $s ): string
	{
		return \htmlspecialchars( $s, \ENT_QUOTES, 'UTF-8' );
	}
}
