<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2022, Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: 2020-2022, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\browserauth;

use Exception;

class ShibbolethAuth implements BrowserAuthInterface
{
	/** @var ?string */
	protected $samlIdp;

	/** @var string */
	protected $shibHandlerUrl;

	/** @var array<string> */
	protected $authzAttributeValue;

	/** @var array<string,string> */
	private $params;

	/** @var string */
	private $userIdAttribute;

	/** @var ?string */
	private $realmSelectorAttribute;

	/** @var array<string> */
	private $realmMap;

	public function __construct( array $params )
	{
		$samlIdp = $params['samlIdp'] ?? null;
		$shibHandlerUrl = $params['shibHandlerUrl'] ?? '/Shibboleth.sso';
		$authzAttributeValue = $params['authzAttributeValue'] ?? [];
		$userIdAttribute = $params['userIdAttribute'] ?? 'REMOTE_USER';
		$realmSelectorAttribute = $params['realmSelectorAttribute'] ?? $params['userRealmPrefixAttribute'] ?? null;
		$realmMap = $params['realmMap'] ?? $params['userRealmPrefixValueMap'] ?? [];

		$this->samlIdp = $samlIdp;
		$this->shibHandlerUrl = $shibHandlerUrl;
		$this->authzAttributeValue = $authzAttributeValue;
		$this->userIdAttribute = $userIdAttribute;
		$this->realmSelectorAttribute = $realmSelectorAttribute;
		$this->realmMap = $realmMap;

		$this->params = $params;
	}

	public function requireAuth(): string
	{
		$user = '';

		if ( !\array_key_exists( 'REQUEST_URI', $_SERVER ) ) {
			throw new \Exception( 'REQUEST_URI not present' );
		}
		if ( !\array_key_exists( $this->userIdAttribute, $_SERVER ) || empty($_SERVER[$this->userIdAttribute])) {
			if ( null !== $this->samlIdp ) {
				\header( 'Location: ' . $this->shibHandlerUrl . '/Login?' . \http_build_query( ['entityID' => $this->samlIdp, 'target' => $_SERVER['REQUEST_URI']] ) );
				exit;
			}
			\header( 'Location: ' . $this->shibHandlerUrl . '/Login?' . \http_build_query( ['target' => $_SERVER['REQUEST_URI']] ) );
			exit;
		}

		if ( \array_key_exists( $this->userIdAttribute, $_SERVER ) && \is_string( $_SERVER[$this->userIdAttribute] ) && !empty($_SERVER[$this->userIdAttribute]) ) {
			$user = \explode(';', $_SERVER[$this->userIdAttribute])[0];
		}

		// authzAttributeValue validates SAML attributes against the attribute-value map for additional authorization
		if ( \count( $this->authzAttributeValue) > 0 ) {
			// can wrap this around try {} / catch {} if we need nicer error handling
			static::checkAuthzAttributeValue( $this->authzAttributeValue );
		}

		return $user;
	}

	/**
	 * @param array $params @unused-param
	 */
	public function guessRealm( array $params ): ?string
	{
		return null;
	}

	/**
	 * Perform authorization based on SAML attributes
	 */
	public function checkAuthzAttributeValue( array $authzAttributeValue ): void
	{
		foreach ( $authzAttributeValue as $attribute => $value ) {
			if ( !\array_key_exists( $attribute, $_SERVER ) || !\is_string( $_SERVER[$attribute] ) ) {
				throw new \Exception( "Attribute {$attribute} not present or invalid type" );
			}
			if ( \is_array( $value ) ) {
				if ( !\array_intersect( $value, \explode(';', $_SERVER[$attribute])) ) {
					throw new \Exception( "Attribute {$attribute} does not have one of the permitted values" );
				}
			}
			if ( \is_string( $value ) ) {
				if ( !\array_intersect( [$value], \explode(';', $_SERVER[$attribute])) ) {
					throw new \Exception( "Attribute {$attribute} does not have the permitted value of {$value}" );
				}
			}
		}
	}

	/**
	 * @param ?string $redirect @unused-param
	 */
	public function getLogoutURL( string $redirect = null ): ?string
	{
		return null;
	}

	public function getRealm(): ?string
	{
		$realmSelectorAttribute = $this->realmSelectorAttribute;
		if ( null === $realmSelectorAttribute ) {
			return null;
		}

		if ( !\array_key_exists( $realmSelectorAttribute, $_SERVER ) || !\is_string( $_SERVER[$realmSelectorAttribute] ) ) {
			return null;
		}

		$realmSelectors = \explode(';', $_SERVER[$realmSelectorAttribute]);

		// if there is an realmMap, iterate over its values (order might be important) and return
		if ( \count( $this->realmMap ) > 0 ) {
			foreach ( $this->realmMap as $attribute => $value ) {
				if ( \in_array( $attribute, $realmSelectors, true ) ) {
					return $value;
				}
			}

			// No match for the realmMap; if we return null the default realm would be used,
			// but that might not be what we want.  We will first check if there is a '*' entry in the map,
			// and use that.  If nothing is available, we throw an exception because we cannot decide on a realm
			if ( \array_key_exists( '*', $this->realmMap ) ) {
				// The value of '*' might be null in order to trigger the default realm anyway,
				// but then the administrator has explicitly decided this, so it's fine

				return $this->realmMap['*'];
			}

			throw new MismatchRealmSelectorException( $realmSelectors, $realmSelectorAttribute );
		}

		// If there is no map, there is only one attribute, we return that as the realm;
		// this works well for eduPersonPrimaryAffiliation, for example.
		if ( 1 === \count( $realmSelectors ) ) {
			return \reset( $realmSelectors );
		}

		// The selector attribute is specified, but we didn't get any match anywhere.
		// We cannot find a realm,
		throw new MismatchRealmSelectorException( $realmSelectors, $realmSelectorAttribute );
	}
}
