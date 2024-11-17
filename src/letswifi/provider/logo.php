<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\provider;

use Closure;
use DomainException;
use letswifi\Config;

class Logo
{
	/**
	 * @param Closure():string $emit
	 */
	public function __construct( private readonly Closure $imageGenerator, public readonly string $contentType )
	{
	}

	public static function fromConfig( Config $logo ): self
	{
		$contentType = $logo->getString( 'content_type' );

		return new self( imageGenerator: static fn(): string => $logo->getString( 'bytes' ), contentType: $contentType );
	}

	public function getBytes(): string
	{
		$f = $this->imageGenerator;

		return $f();
	}

	public function emit(): never
	{
		if ( \headers_sent() ) {
			throw new DomainException( 'Headers already sent' );
		}
		$bytes = $this->getBytes();
		if ( \headers_sent() ) {
			throw new DomainException( 'Headers sent when trying to get logo payload' );
		}
		\header( 'Content-Type: ' . $this->contentType );
		\header( 'Content-Length: ' . \strlen( $bytes ) );

		exit( $bytes );
	}
}
