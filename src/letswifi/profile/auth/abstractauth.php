<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2022, Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: 2020-2022, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\profile\auth;

use InvalidArgumentException;
use fyrkat\openssl\X509;

abstract class AbstractAuth implements Auth
{
	/** @var array<X509> */
	private $caCertificates;

	/** @var array<string> */
	private $serverNames;

	/**
	 * @param array<X509>   $caCertificates Trusted CA certificates
	 * @param array<string> $serverNames    Accepted server names
	 */
	public function __construct( array $caCertificates, array $serverNames )
	{
		$this->caCertificates = $caCertificates;
		$this->serverNames = $serverNames;
	}

	/**
	 * @return array<X509>
	 */
	public function getServerCACertificates(): array
	{
		return $this->caCertificates;
	}

	/**
	 * @return array<string>
	 */
	public function getServerNames(): array
	{
		return $this->serverNames;
	}

	/**
	 * Strip off BEGIN and END stanzas and remove whitespace
	 *
	 * @param string $pem PEM-encoded certificate
	 *
	 * @return string base64 encoded DER certificate
	 */
	public static function pemToBase64Der( string $pem ): string
	{
		$pem = \trim( $pem );
		if ( '-----BEGIN CERTIFICATE-----' !== \substr( $pem, 0, 27 ) || '-----END CERTIFICATE-----' !== \substr( $pem, -25 ) ) {
			throw new InvalidArgumentException( 'Expected PEM string' );
		}

		$cutted = \substr( $pem, 27, -25 );

		return \str_replace( ["\n", "\r"], ['', ''], $cutted );
	}
}
