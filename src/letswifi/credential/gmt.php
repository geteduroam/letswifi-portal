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
use DateTimeZone;

/** @internal */
trait GMT
{
	protected static function dateTimeFromGmt( ?string $datetimeGmt ): ?DateTimeImmutable
	{
		$result = false;
		if ( null !== $datetimeGmt ) {
			$gmt = new DateTimeZone( 'GMT' );
			$result = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $datetimeGmt, $gmt );
		}

		return $result ?: null;
	}
}
