<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\error;

use Throwable;

class RealmMismatchException extends ForbiddenException
{
	public function __construct( ?string $realmId = null, ?Throwable $previous = null )
	{
		parent::__construct( null === $realmId ? 'No default realm is available for the current user' : "Realm {$realmId} is not available for the current user", $previous );
	}
}
