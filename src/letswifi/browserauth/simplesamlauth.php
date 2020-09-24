<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2020, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\browserauth;

use OutOfBoundsException;
use Throwable;

class SimpleSAMLAuth implements BrowserAuthInterface
{
	/**
	 * @psalm-suppress PropertyNotSetInConstructor Yes it is!
	 */
	protected $as;

	/** @var ?string */
	protected $samlIdp;

	/**
	 * @psalm-suppress PropertyNotSetInConstructor Yes it is!
	 *
	 * @var string
	 */
	private $userIdAttribute;

	/** @var ?array<string,array<string>> */
	private $attributes = null;

	/**
	 * @psalm-suppress UndefinedClass We don't have a dependency on SimpleSAMLphp
	 * @psalm-suppress UnresolvableInclude We don't know where SimpleSAMLphp is
	 * @suppress PhanUndeclaredClassMethod We don't have a dependency on SimpleSAMLphp
	 *
	 * @param array<string,string> $params
	 */
	public function __construct( array $params )
	{
		if ( \array_key_exists( 'autoloadInclude', $params ) ) {
			require $params['autoloadInclude'];
		}
		$authSource = \array_key_exists( 'authSource', $params ) ? $params['authSource'] : 'default-sp';
		$userIdAttribute = \array_key_exists( 'userIdAttribute', $params ) ? $params['userIdAttribute'] : 'eduPersonPrincipalName';
		$this->samlIdp = \array_key_exists( 'samlIdp', $params ) ? $params['samlIdp'] : null;
		$this->as = new \SimpleSAML\Auth\Simple( $authSource );
		$this->userIdAttribute = $userIdAttribute;
	}

	/**
	 * @psalm-suppress UndefinedClass We don't have a dependency on SimpleSAMLphp
	 * @suppress PhanUndeclaredClassMethod We don't have a dependency on SimpleSAMLphp
	 */
	public function requireAuth(): string
	{
		$params = [];
		if ( null !== $this->samlIdp ) {
			$params['saml:idp'] = $this->samlIdp;
		}

		$this->as->requireAuth( $params );

		static::checkIdP( $this->samlIdp, $this->as->getAuthData( 'saml:sp:IdP' ) );

		return $this->getSingleAttributeValue( $this->userIdAttribute );
	}

	/**
	 * @psalm-suppress UndefinedClass We don't have a dependency on SimpleSAMLphp
	 * @suppress PhanUndeclaredClassMethod We don't have a dependency on SimpleSAMLphp
	 */
	public function getSingleAttributeValue( string $key ): string
	{
		if ( null === $this->attributes ) {
			$this->attributes = $this->as->getAttributes();
			\assert( \is_array( $this->attributes ), 'SimpleSAMLphp always returns an array' );
		}
		if ( !\array_key_exists( $key, $this->attributes ) ) {
			throw new OutOfBoundsException( "Attribute ${key} not present in SAML assertion" );
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

	private static function checkIdP( ?string $expectedIdP, ?string $providedIdP ): void
	{
		if ( null !== $expectedIdP && $expectedIdP !== $providedIdP ) {
			throw new MismatchIdpException( $expectedIdP, $providedIdP );
		}
	}
}
