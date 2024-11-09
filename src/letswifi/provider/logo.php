<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\provider;

use DomainException;

class Logo
{
	public function __construct( private readonly string $bytes, public readonly string $contentType )
	{
	}

	public static function fromArray( array $logo ): self
	{
		return new self( bytes: $logo['bytes'], contentType: $logo['content_type'] );
	}

	public function getBytes(): string
	{
		return $this->bytes;
	}

	public function emit(): never
	{
		if ( \headers_sent() ) {
			throw new DomainException( 'Headers already sent' );
		}
		\header( 'Content-Type: ' . $this->contentType );
		\header( 'Content-Length: ' . \strlen( $this->bytes ) );

		exit( $this->bytes );
	}
}
