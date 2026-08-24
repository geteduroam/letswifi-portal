<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\configmap;

use fyrkat\configmap\ConfigurationException;
use fyrkat\configmap\Dictionary;
use fyrkat\configmap\DictionaryDir;
use fyrkat\openssl\PrivateKey;
use fyrkat\openssl\X509;

class DictionaryPemDir extends DictionaryDir
{
	public const EXT = '.pem';

	public const SIGIL = 'pemdir';

	public const TFILE = DictionaryPemFile::class;

	/**
	 * @return iterable<string,callable(Dictionary,string,string):mixed>
	 */
	public static function sigils(): iterable
	{
		yield from DictionaryPemFile::sigils();
	}

	public function offsetSet( mixed $offset, mixed $value ): void
	{
		if ( !\is_array( $value ) ) {
			throw new ConfigurationException( $this->formatPath( $offset ) . ': Cannot write pem file; input must be array' );
		}
		if ( !\array_key_exists( 'x509', $value ) ) {
			throw new ConfigurationException( $this->formatPath( $offset ) . ': Cannot write pem file; x509 must be set' );
		}
		if ( !$value['x509'] instanceof X509 ) {
			throw new ConfigurationException( $this->formatPath( $offset ) . ': x509 must be an X509 object' );
		}
		if ( \array_key_exists( 'key', $value ) && !$value['key'] instanceof PrivateKey ) {
			throw new ConfigurationException( $this->formatPath( $offset ) . ': key must be PrivateKey object' );
		}
		$this->importCertificate( $value['x509'], $value['key'] ?? null, $offset );
	}

	public function importCertificate( X509 $x509, ?PrivateKey $privateKey = null, ?string $subjectHint = null ): string
	{
		$subject = $x509->getSubject( longNames: false )->__toString();
		if ( \str_contains( $subject, '/' ) || \str_contains( $subject, '\\' ) ) {
			throw new ConfigurationException( "{$subject}: Certificate subject contains invalid sequences" );
		}
		if ( null !== $subjectHint && $subjectHint !== $subject ) {
			throw new ConfigurationException( "{$subjectHint}: Certificate subject does not match subject hint" );
		}

		foreach ( $this->baseDirs as $baseDir ) {
			break; // Replace foreach with array_first for PHP>=8.5.0
		}
		$filename = $baseDir . \DIRECTORY_SEPARATOR . $this->dirPath . \DIRECTORY_SEPARATOR . "{$subject}.pem";
		$privKeyPem = \trim( $privateKey?->getPrivateKeyPem( null ) ?? '' );
		if ( !empty( $privKeyPem ) ) {
			$privKeyPem .= "\n";
		}
		\file_put_contents( $filename, \implode( "\n", [
			\trim( $x509->getX509Pem() ),
			$privKeyPem,
		] ) );

		return $subject;
	}
}
