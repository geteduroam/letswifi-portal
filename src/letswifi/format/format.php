<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\format;

use DomainException;
use fyrkat\openssl\PKCS7;
use letswifi\credential\Credential;

abstract class Format
{
	final public function __construct(
		protected readonly Credential $credential,
		protected readonly ?PKCS7 $profileSigner = null,
		protected readonly ?string $passphrase = null,
	) {
	}

	public static function getFormatter(
		string $type,
		Credential $credential,
		?PKCS7 $profileSigner = null,
		?string $passphrase = null,
	): self {
		if ( !\preg_match( '/[^0-9a-z\\-]|--|^[0-9]|^-|-$/m', $type ) ) {
			$className = \sprintf( '\\letswifi\\format\\%sFormat', \ucfirst(
				\preg_replace_callback(
					'/(\\-[a-z])/',
					static fn( $m ) => \strtoupper( \ltrim( $m[1], '-' ) ), $type ),
			),
			);
			if ( !\str_contains( $className, '-' ) && \class_exists( $className ) && \is_subclass_of( $className, self::class ) ) {
				return new $className( $credential, $profileSigner, $passphrase );
			}
		}

		throw new DomainException( 'Invalid formatter type specified: ' . $type );
	}

	public function emit(): never
	{
		if ( \headers_sent() ) {
			throw new DomainException( 'Cannot emit file, headers already sent' );
		}
		$payload = $this->generate();
		if ( \headers_sent() ) {
			// TODO revoke credential here?
			throw new DomainException( 'Cannot emit file, headers sent while generating file' );
		}
		\header( 'Content-Disposition: attachment; filename="' . $this->getFilename() . '"' );
		\header( 'Content-Type: ' . $this->getContentType() );
		\header( 'Content-Length: ' . \strlen( $payload ) );

		exit( $payload );
	}

	public function getFilename(): string
	{
		$identifier = $this->getIdentifier();
		$datetime = \date( 'YmdHis' );
		$extension = $this->getFileExtension();

		return "{$identifier}.{$datetime}.{$extension}";
	}

	public function getIdentifier(): string
	{
		return \implode( '.', \array_reverse( \explode( '.', $this->credential->realm->realmId ) ) );
	}

	abstract public function getFileExtension(): string;

	abstract public function getContentType(): string;

	abstract public function generate(): string;

	protected static function e( string $s ): string
	{
		return \htmlspecialchars( $s, \ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Format a very long string, such as a PEM, with a fixed length and tab indentation
	 */
	protected static function columnFormat( string $data, int $length = 52, int $indentation = 0 ): string
	{
		\assert( 0 < $length, '$length must be at least 1' );

		return \implode(
			"\n" . \str_repeat( "\t", $indentation ),
			\str_split( $data, $length ),
		);
	}

	/**
	 * @see https://stackoverflow.com/a/15875555
	 */
	protected static function uuidgen(): string
	{
		$data = \random_bytes( 16 );
		$data[6] = \chr( \ord( $data[6] ) & 0x0F | 0x40 ); // set version to 0100
		$data[8] = \chr( \ord( $data[8] ) & 0x3F | 0x80 ); // set bits 6-7 to 10

		return \vsprintf( '%s%s-%s-%s-%s-%s%s%s', \str_split( \bin2hex( $data ), 4 ) );
	}
}
