<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2021, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * Copyright: 2020-2021, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\browserauth;

use Exception;
use OutOfBoundsException;
use Throwable;

class SimpleSAMLAuth implements BrowserAuthInterface
{
	protected $as;

	/** @var ?string */
	protected $samlIdp;

	/** @var array<string> */
	protected $idpList;

	/** @var bool */
	protected $verifyAuthenticatingAuthority;

	/** @var array<string> */
	protected $authzAttributeValue;

	/**
	 * The attribute containing the user ID, null for nameID
	 *
	 * @var ?string
	 */
	private $userIdAttribute;

	/** @var ?string */
	private $userRealmPrefixAttribute;

	/** @var array<string> */
	private $userRealmPrefixValueMap;

	/** @var ?array<string,array<string>> */
	private $attributes = null;

	/**
	 * @psalm-suppress UndefinedClass We don't have a dependency on SimpleSAMLphp
	 * @psalm-suppress UnresolvableInclude We don't know where SimpleSAMLphp is
	 * @suppress PhanUndeclaredClassMethod We don't have a dependency on SimpleSAMLphp
	 *
	 * @param array<string,array<string>|string> $params
	 */
	public function __construct( array $params )
	{
		if ( \array_key_exists( 'autoloadInclude', $params ) ) {
			require $params['autoloadInclude'];
		}
		$authSource = \array_key_exists( 'authSource', $params ) ? $params['authSource'] : 'default-sp';
		$userIdAttribute = \array_key_exists( 'userIdAttribute', $params ) ? $params['userIdAttribute'] : null;
		$userRealmPrefixAttribute = \array_key_exists( 'userRealmPrefixAttribute', $params ) ? $params['userRealmPrefixAttribute'] : null;
		$userRealmPrefixValueMap = \array_key_exists( 'userRealmPrefixValueMap', $params ) ? $params['userRealmPrefixValueMap'] : [];
		$samlIdp = \array_key_exists( 'samlIdp', $params ) ? $params['samlIdp'] : null;
		$idpList = \array_key_exists( 'idpList', $params ) ? $params['idpList'] : [];
		$verifyAuthenticatingAuthority = \array_key_exists( 'verifyAuthenticatingAuthority', $params ) ? $params['verifyAuthenticatingAuthority'] : true;
		$authzAttributeValue = \array_key_exists( 'authzAttributeValue', $params ) ? $params['authzAttributeValue'] : [];
		\assert( \is_string( $userIdAttribute ), 'userIdAttribute must be string' );
		\assert( \is_string( $userRealmPrefixAttribute ), 'userRealmPrefixAttribute must be string' );
		\assert( \is_array( $userRealmPrefixValueMap ), 'userRealmPrefixValueMap must be array' );
		\assert( \is_string( $samlIdp ) || null === $samlIdp, 'samlIdp must be string if provided' );
		\assert( \is_array( $idpList ), 'idpList must be array if provided' );
		\assert( \is_array( $authzAttributeValue ), 'authzAttributeValue must be array if provided' );
		\assert( \is_bool( $verifyAuthenticatingAuthority ), 'verifyAuthenticatingAuthority must be a boolean if provided' );
		$this->samlIdp = $samlIdp;
		$this->idpList = $idpList;
		$this->verifyAuthenticatingAuthority = $verifyAuthenticatingAuthority;
		$this->authzAttributeValue = $authzAttributeValue;
		$this->as = new \SimpleSAML\Auth\Simple( $authSource );
		$this->userIdAttribute = $userIdAttribute;
		$this->userRealmPrefixAttribute = $userRealmPrefixAttribute;
		$this->userRealmPrefixValueMap = $userRealmPrefixValueMap;
	}

	/**
	 * @suppress PhanUndeclaredClassMethod We don't have a dependency on SimpleSAMLphp
	 */
	public function requireAuth(): string
	{
		$params = [];
		if ( null !== $this->samlIdp ) {
			$params['saml:idp'] = $this->samlIdp;
		}
		if ( \count( $this->idpList ) > 0 ) {
			$params['saml:IDPList'] = $this->idpList;
			if ( null !== $this->samlIdp ) {
				// SimpleSAMLphp does a local check of the IDPList and will throw an exception
				// if the saml:IDPList param doesn't contain any IdP it knows about,
				// which is silly because it should just forward this list to the proxy,
				// not handle it by itself.
				// This check is in the Auth\Source\SP::authenticate() function.
				$params['saml:IDPList'][] = $this->samlIdp;
			}
		}

		$this->as->requireAuth( $params );

		static::checkIdP( $this->samlIdp, $this->as->getAuthData( 'saml:sp:IdP' ) );

		// Also check the saml:AuthenticatingAuthority for the entries in idpList if there is one and it isn't explicitly disabled
		if ( \count( $this->idpList ) > 0 && $this->verifyAuthenticatingAuthority ) {
			static::checkIdPList( $this->idpList, $this->as->getAuthData('saml:AuthenticatingAuthority') );
		}

		// authzAttributeValue validates SAML attributes against the attribute-value map for additional authorization
		if ( \count( $this->authzAttributeValue) > 0 ) {
			// can wrap this around try {} / catch {} if we need nicer error handling
			static::checkAuthzAttributeValue( $this->authzAttributeValue );
		}

		if ( null === $this->userIdAttribute ) {
			// in SimpleSAMLphp version 2 ->value should be replaced with ->getValue()
			return $this->as->getAuthData( 'saml:sp:NameID' )->value;
		}

		return $this->getSingleAttributeValue( $this->userIdAttribute );
	}

	/**
	 * @suppress PhanUndeclaredClassMethod We don't have a dependency on SimpleSAMLphp
	 */
	public function getUserRealmPrefix(): ?string
	{
		if ( null === $this->attributes ) {
			$this->attributes = $this->as->getAttributes();
			\assert( \is_array( $this->attributes ), 'SimpleSAMLphp always returns an array' );
		}
		if ( !\array_key_exists( $this->userRealmPrefixAttribute, $this->attributes ) ) {
			return null;
		}
		\assert( \is_array( $this->attributes[$this->userRealmPrefixAttribute] ), 'SimpleSAMLphp always returns attributes as array' );

		// if there is an userRealmPrefixValueMap, iterate over its values (order might be important) and return
		if ( \count( $this->userRealmPrefixValueMap ) > 0 ) {
			foreach ( $this->userRealmPrefixValueMap as $attribute => $value ) {
				if ( \in_array( $attribute, $this->attributes[$this->userRealmPrefixAttribute], true ) ) {
					return $value;
				}
			}

			return null;
		}

		// if there is no map, we hope there's just one attribute, ie. the eduPersonPrimaryAffiliation
		if ( 1 === \count( $this->attributes[$this->userRealmPrefixAttribute] ) ) {
			return \reset( $this->attributes[$this->userRealmPrefixAttribute] );
		}

		// we're unsure if there is no map, no prefix
		return null;
	}

	/**
	 * @suppress PhanUndeclaredClassMethod We don't have a dependency on SimpleSAMLphp
	 */
	public function getSingleAttributeValue( string $key ): string
	{
		if ( null === $this->attributes ) {
			$this->attributes = $this->as->getAttributes();
			\assert( \is_array( $this->attributes ), 'SimpleSAMLphp always returns an array' );
		}
		if ( !\array_key_exists( $key, $this->attributes ) ) {
			$shibKey = $this->attributeToShib( $key );
			if ( !\array_key_exists( $shibKey, $this->attributes ) ) {
				throw new OutOfBoundsException( "Attribute ${key} not present in SAML assertion" );
			}
			$key = $shibKey;
		}
		\assert( \is_array( $this->attributes[$key] ), 'SimpleSAMLphp always returns attributes as array' );
		if ( 1 !== \count( $this->attributes[$key] ) ) {
			throw new OutOfBoundsException( "Attribute ${key} was expected to have exactly 1 value, but has " . \count( $this->attributes[$key] ) );
		}
		$result = \reset( $this->attributes[$key] );
		\assert( \is_string( $result ), 'Attributes returned by SimpleSAMLphp are always of type string' );

		return $result;
	}

	/**
	 * Perform authorization based on SAML attributes
	 *
	 * @suppress PhanUndeclaredClassMethod We don't have a dependency on SimpleSAMLphp
	 */
	public function checkAuthzAttributeValue( array $authzAttributeValue ): void
	{
		if ( null === $this->attributes ) {
			$this->attributes = $this->as->getAttributes();
			\assert( \is_array( $this->attributes ), 'SimpleSAMLphp always returns an array' );
		}
		foreach ( $authzAttributeValue as $attribute => $value ) {
			if ( !\array_key_exists( $attribute, $this->attributes ) ) {
				throw new Exception( "Attribute ${attribute} not present in SAML assertion" );
			}
			if ( \is_array( $value ) ) {
				if ( !\array_intersect( $value, $this->attributes[$attribute]) ) {
					throw new Exception( "Attribute ${attribute} does not have one of the permitted values" );
				}
			}
			if ( \is_string( $value ) ) {
				if ( !\array_intersect( [$value], $this->attributes[$attribute]) ) {
					throw new Exception( "Attribute ${attribute} does not have the permitted value of ${value}" );
				}
			}
		}
	}

	/**
	 * @suppress PhanUndeclaredClassMethod We don't have a dependency on SimpleSAMLphp
	 */
	public function getLogoutUrl( ?string $redirect = null ): string
	{
		return $this->as->getLogoutURL( $redirect );
	}

	/**
	 * @suppress PhanUndeclaredClassMethod We don't have a dependency on SimpleSAMLphp
	 */
	public function guessRealm( array $params ): ?string
	{
		$providedIdP = $this->as->getAuthData( 'saml:sp:IdP' );
		/** @var ?string */
		$result = null;
		foreach ( $params as $candidate => $p ) {
			try {
				if ( \array_key_exists( 'samlIdp', $p ) ) {
					static::checkIdP( $p['samlIdp'], $providedIdP );
					if ( null !== $result ) {
						return null;
					}

					$result = $candidate;
				}
			} catch ( Throwable $_ ) {
				/* we're guessing so no need to handle */
			}
		}

		return $result;
	}

	/**
	 * Check that the provided IdP equals the expected IdP
	 *
	 * @throws MismatchIdpException If an IdP was expected, and the provided one doesn't match it
	 */
	private static function checkIdP( ?string $expectedIdP, ?string $providedIdP ): void
	{
		if ( null !== $expectedIdP && $expectedIdP !== $providedIdP ) {
			throw new MismatchIdpException( $expectedIdP, $providedIdP );
		}
	}

	/**
	 * Check that the saml:AuthenticatingAuthority contains the IdPList used in scoping
	 *
	 * @throws MismatchIdpException If the IdPList is not entirely present in the saml:AuthenticatingAuthority
	 */
	private static function checkIdPList( array $expectedIdPList, array $authenticatingAuthority ): void
	{
		if ( $expectedIdPList !== \array_intersect($expectedIdPList, $authenticatingAuthority) ) {
			throw new MismatchIdpException( $expectedIdPList[0], $authenticatingAuthority[0] );
		}
	}

	/**
	 * Take a human readable attribute name and return the Shibboleth OID
	 *
	 * Useful for when the IdP is Shibboleth.
	 *
	 * If no match is found, it will just return the input.
	 *
	 * @param string $attr Attribute name (e.g. eduPersonPrincipalName)
	 *
	 * @return string Shibboleth OID
	 */
	private static function attributeToShib( string $attr ): string
	{
		switch ( $attr ) {
			case 'uid': return 'urn:oid:0.9.2342.19200300.100.1.1';
			case 'mail': return 'urn:oid:0.9.2342.19200300.100.1.3';
			case 'manager': return 'urn:oid:0.9.2342.19200300.100.1.10';
			case 'homePhone': return 'urn:oid:0.9.2342.19200300.100.1.20';
			case 'homePostalAddress': return 'urn:oid:0.9.2342.19200300.100.1.39';
			case 'mobile': return 'urn:oid:0.9.2342.19200300.100.1.41';
			case 'pager': return 'urn:oid:0.9.2342.19200300.100.1.42';
			case 'uniqueIdentifier': return 'urn:oid:0.9.2342.19200300.100.1.44';
			case 'audio': return 'urn:oid:0.9.2342.19200300.100.1.55';
			case 'jpegPhoto': return 'urn:oid:0.9.2342.19200300.100.1.60';
			case 'labeledURI': return 'urn:oid:1.3.6.1.4.1.250.1.57';
			case 'eduPersonAffiliation': return 'urn:oid:1.3.6.1.4.1.5923.1.1.1.1';
			case 'eduPersonNickname': return 'urn:oid:1.3.6.1.4.1.5923.1.1.1.2';
			case 'eduPersonOrgDN': return 'urn:oid:1.3.6.1.4.1.5923.1.1.1.3';
			case 'eduPersonOrgUnitDN': return 'urn:oid:1.3.6.1.4.1.5923.1.1.1.4';
			case 'eduPersonPrimaryAffiliation': return 'urn:oid:1.3.6.1.4.1.5923.1.1.1.5';
			case 'eduPersonPrincipalName': return 'urn:oid:1.3.6.1.4.1.5923.1.1.1.6';
			case 'eduPersonEntitlement': return 'urn:oid:1.3.6.1.4.1.5923.1.1.1.7';
			case 'eduPersonPrimaryOrgUnitDN': return 'urn:oid:1.3.6.1.4.1.5923.1.1.1.8';
			case 'eduPersonScopedAffiliation': return 'urn:oid:1.3.6.1.4.1.5923.1.1.1.9';
			case 'eduPersonTargetedID': return 'urn:oid:1.3.6.1.4.1.5923.1.1.1.10';
			case 'eduPersonAssurance': return 'urn:oid:1.3.6.1.4.1.5923.1.1.1.11';
			case 'eduPersonPrincipalNamePrior': return 'urn:oid:1.3.6.1.4.1.5923.1.1.1.12';
			case 'eduPersonUniqueId': return 'urn:oid:1.3.6.1.4.1.5923.1.1.1.13';
			case 'eduPersonOrcid': return 'urn:oid:1.3.6.1.4.1.5923.1.1.1.16';
			case 'isMemberOf': return 'urn:oid:1.3.6.1.4.1.5923.1.5.1.1';
			case 'schacYearOfBirth': return 'urn:oid:1.3.6.1.4.1.25178.1.0.2.3';
			case 'schacMotherTongue': return 'urn:oid:1.3.6.1.4.1.25178.1.2.1';
			case 'schacGender': return 'urn:oid:1.3.6.1.4.1.25178.1.2.2';
			case 'schacDateOfBirth': return 'urn:oid:1.3.6.1.4.1.25178.1.2.3';
			case 'schacPlaceOfBirth': return 'urn:oid:1.3.6.1.4.1.25178.1.2.4';
			case 'schacCountryOfCitizenship': return 'urn:oid:1.3.6.1.4.1.25178.1.2.5';
			case 'schacSn1': return 'urn:oid:1.3.6.1.4.1.25178.1.2.6';
			case 'schacSn2': return 'urn:oid:1.3.6.1.4.1.25178.1.2.7';
			case 'schacPersonalTitle': return 'urn:oid:1.3.6.1.4.1.25178.1.2.8';
			case 'schacHomeOrganization': return 'urn:oid:1.3.6.1.4.1.25178.1.2.9';
			case 'schacHomeOrganizationType': return 'urn:oid:1.3.6.1.4.1.25178.1.2.10';
			case 'schacCountryOfResidence': return 'urn:oid:1.3.6.1.4.1.25178.1.2.11';
			case 'schacUserPresenceID': return 'urn:oid:1.3.6.1.4.1.25178.1.2.12';
			case 'schacPersonalPosition': return 'urn:oid:1.3.6.1.4.1.25178.1.2.13';
			case 'schacPersonalUniqueCode': return 'urn:oid:1.3.6.1.4.1.25178.1.2.14';
			case 'schacPersonalUniqueID': return 'urn:oid:1.3.6.1.4.1.25178.1.2.15';
			case 'schacExpiryDate': return 'urn:oid:1.3.6.1.4.1.25178.1.2.17';
			case 'schacUserPrivateAttribute': return 'urn:oid:1.3.6.1.4.1.25178.1.2.18';
			case 'schacUserStatus': return 'urn:oid:1.3.6.1.4.1.25178.1.2.19';
			case 'schacProjectMembership': return 'urn:oid:1.3.6.1.4.1.25178.1.2.20';
			case 'schacProjectSpecificRole': return 'urn:oid:1.3.6.1.4.1.25178.1.2.21';
			case 'cn': return 'urn:oid:2.5.4.3';
			case 'sn': return 'urn:oid:2.5.4.4';
			case 'l': return 'urn:oid:2.5.4.7';
			case 'st': return 'urn:oid:2.5.4.8';
			case 'o': return 'urn:oid:2.5.4.10';
			case 'ou': return 'urn:oid:2.5.4.11';
			case 'title': return 'urn:oid:2.5.4.12';
			case 'description': return 'urn:oid:2.5.4.13';
			case 'postalAddress': return 'urn:oid:2.5.4.16';
			case 'postalCode': return 'urn:oid:2.5.4.17';
			case 'postOfficeBox': return 'urn:oid:2.5.4.18';
			case 'telephoneNumber': return 'urn:oid:2.5.4.20';
			case 'facsimileTelephoneNumber(defined': return 'urn:oid:2.5.4.23';
			case 'seeAlso': return 'urn:oid:2.5.4.34';
			case 'userPassword': return 'urn:oid:2.5.4.35';
			case 'userCertificate': return 'urn:oid:2.5.4.36';
			case 'givenName': return 'urn:oid:2.5.4.42';
			case 'initials': return 'urn:oid:2.5.4.43';
			case 'x500uniqueIdentifier': return 'urn:oid:2.5.4.45';
			case 'preferredLanguage': return 'urn:oid:2.16.840.1.113730.3.1.39';
			case 'userSMIMECertificate': return 'urn:oid:2.16.840.1.113730.3.1.40';
			case 'displayName': return 'urn:oid:2.16.840.1.113730.3.1.241';
			case 'street': return 'urn:oid::2.5.4.9';
			case 'pairwise-id': return 'urn:oasis:names:tc:SAML:attribute:pairwise-id';
			case 'subject-id': return 'urn:oasis:names:tc:SAML:attribute:subject-id';
			default: return $attr;
		}
	}
}
