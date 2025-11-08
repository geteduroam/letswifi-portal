<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\credential;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/** @internal */
trait UTC
{
	// public const DATE_FORMAT = 'Y-m-d H:i:s'; // TODO Uncomment for PHP 8.2+

	/**
	 * @param ?string $datetimeGmt Y-m-d H:i:s formatted string in UTC
	 */
	protected static function dateTimeFromUtc( ?string $datetimeGmt ): ?DateTimeImmutable
	{
		$result = false;
		if ( null !== $datetimeGmt ) {
			$gmt = new DateTimeZone( 'UTC' );
			$result = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $datetimeGmt, $gmt );
		}

		return $result ?: null;
	}

	protected static function formatUtc( DateTimeInterface|int $dateTime ): string
	{
		return \is_int( $dateTime )
			? \gmdate( 'Y-m-d H:i:s', $dateTime )
			: \gmdate( 'Y-m-d H:i:s', $dateTime->getTimestamp() );
	}
}
