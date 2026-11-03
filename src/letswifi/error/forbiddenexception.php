<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\error;

use Throwable;

class ForbiddenException extends UserException
{
	public function __construct( string $message, ?Throwable $previous = null )
	{
		parent::__construct( $message, 403, $previous );
	}
}
