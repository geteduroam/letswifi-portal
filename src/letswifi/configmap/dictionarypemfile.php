<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\configmap;

use DomainException;
use fyrkat\configmap\Dictionary;
use fyrkat\configmap\DictionaryFile;
use fyrkat\openssl\OpenSSLException;
use fyrkat\openssl\PrivateKey;
use fyrkat\openssl\X509;

class DictionaryPemFile extends DictionaryFile
{
	public const EXT = '.pem';

	public const TFILE = self::class;

	public const TDIR = DictionaryPemDir::class;

	/**
	 * @return iterable<string,callable(Dictionary,string,string):mixed>
	 */
	public static function sigils(): iterable
	{
		yield DictionaryPemDir::SIGIL => static::dirSigil( DictionaryPemDir::class );
	}

	/**
	 * @psalm-suppress UnresolvableInclude
	 */
	protected function readFile( string $filePath ): mixed
	{
		if ( !\file_exists( $filePath ) ) {
			throw new DomainException( 'PEM file missing: ' . $filePath );
		}

		$x509 = new X509( "file:///{$filePath}" );
		$privateKey = null;

		try {
			$privateKey = new PrivateKey( "file:///{$filePath}" );
		} catch ( OpenSSLException $_ ) {
		}
		$issuer = $x509->getIssuerSubject( longNames: false )->__toString();
		$subject = $x509->getSubject( longNames: false )->__toString();
		\assert( \str_ends_with( $filePath, \DIRECTORY_SEPARATOR . "{$subject}.pem" ) );
		if ( $issuer === $subject ) {
			$issuer = null;
		}

		return \array_filter( [
			'x509' => (string)$x509,
			'key' => $privateKey?->getPrivateKeyPem( null ),
			'issuer' => $issuer,
		] );
	}
}
