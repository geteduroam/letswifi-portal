<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\configuration;

use DomainException;
use fyrkat\openssl\OpenSSLException;
use fyrkat\openssl\PrivateKey;
use fyrkat\openssl\X509;

class DictionaryPemFile extends Dictionary
{
	public function __construct( string $file )
	{
		if ( !\file_exists( $file ) ) {
			throw new DomainException( 'PEM file missing: ' . $file );
		}

		$x509 = new X509( "file:///{$file}" );
		$privateKey = null;

		try {
			$privateKey = new PrivateKey( "file:///{$file}" );
		} catch ( OpenSSLException $_ ) {
		}
		$issuer = $x509->getIssuerSubject( longNames: false )->__toString();
		$subject = $x509->getSubject( longNames: false )->__toString();
		\assert( \str_ends_with( $file, \DIRECTORY_SEPARATOR . "{$subject}.pem" ) );
		if ( $issuer === $subject ) {
			$issuer = null;
		}
		parent::__construct( \array_filter( [
			'x509' => (string)$x509,
			'key' => $privateKey?->getPrivateKeyPem( null ),
			'issuer' => $issuer,
		] ) );
	}
}
