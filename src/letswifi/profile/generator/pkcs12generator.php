<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2022, Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: 2020-2022, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\profile\generator;

use InvalidArgumentException;

use letswifi\profile\auth\Auth;
use letswifi\profile\auth\TlsAuth;
use letswifi\profile\IProfileData;

use UnexpectedValueException;

class PKCS12Generator extends AbstractGenerator
{
	/** @var string */
	protected $password;

	public function __construct( IProfileData $profileData, array $authenticationMethods, string $password = '' )
	{
		parent::__construct( $profileData, $authenticationMethods );
		if ( empty($password) ) {
			$this->password = 'pkcs12';
		} else {
			$this->password = $password;
		}
	}

	/**
	 * Generate the eap-config profile
	 */
	public function generate(): string
	{
		$tlsAuthMethods = \array_filter(
				$this->authenticationMethods,
				static function ( Auth $a ): bool { return $a instanceof TlsAuth && null !== $a->getPKCS12(); },
			);
		if ( 1 !== \count( $tlsAuthMethods ) ) {
			throw new InvalidArgumentException( 'Expected 1 TLS auth method, got ' . \count( $tlsAuthMethods ) );
		}
		$tlsAuthMethod = \reset( $tlsAuthMethods );

		/** @psalm-suppress RedundantCondition */
		\assert( $tlsAuthMethod instanceof TlsAuth );

		if ( $pkcs12 = $tlsAuthMethod->getPKCS12() ) {
			return $pkcs12->getPKCS12Bytes( $this->password );
		}

		throw new UnexpectedValueException( 'Reached unreachable code; PKCS12 was null unexpectedly' );
	}

	public function getFileExtension(): string
	{
		return 'p12';
	}

	public function getContentType(): string
	{
		return 'application/x-pkcs12';
	}
}
