<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\profile;

use Closure;
use DomainException;
use letswifi\configuration\Dictionary;

class Logo
{
	/**
	 * @param Closure():string $emit
	 */
	public function __construct( private readonly Closure $imageGenerator, public readonly string $contentType )
	{
	}

	public static function fromConfig( Dictionary $logo ): ?self
	{
		if ( !$logo->has( 'data' ) ) {
			return null;
		}
		$contentType = $logo->getStringOrNull( 'content_type' );
		if ( null === $contentType && isset( $logo['data#file'] ) ) {
			$ext = \preg_replace( '/^.*\\./', '', $logo['data#file'] );

			switch ( $ext ) {
				case 'jpg':
				case 'jpeg': $contentType = 'image/jpeg'; break;
				case 'png': $contentType = 'image/png'; break;
				case 'svg': $contentType = 'image/svg+xml'; break;
			}
		}
		if ( null === $contentType ) {
			return null;
		}

		return new self( imageGenerator: static fn(): string => $logo->getString( 'data' ), contentType: $contentType );
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
