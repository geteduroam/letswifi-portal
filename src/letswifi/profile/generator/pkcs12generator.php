<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\profile\generator;

use InvalidArgumentException;
use UnexpectedValueException;
use letswifi\profile\auth\Auth;
use letswifi\profile\auth\TlsAuth;

class PKCS12Generator extends AbstractGenerator
{
	/**
	 * Generate the eap-config profile
	 */
	public function generate(): string
	{
		$tlsAuthMethods = \array_filter(
			$this->authenticationMethods,
			static fn ( Auth $a ): bool => $a instanceof TlsAuth && null !== $a->getPKCS12(),
		);
		if ( 1 !== \count( $tlsAuthMethods ) ) {
			throw new InvalidArgumentException( 'Expected 1 TLS auth method, got ' . \count( $tlsAuthMethods ) );
		}
		$tlsAuthMethod = \reset( $tlsAuthMethods );

		/** @psalm-suppress RedundantCondition */
		\assert( $tlsAuthMethod instanceof TlsAuth );

		if ( $pkcs12 = $tlsAuthMethod->getPKCS12() ) {
			// We need 3DES support, since some of our supported clients support nothing else
			return $pkcs12->use3des()->getPKCS12Bytes( $this->passphrase ?: '' );
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
