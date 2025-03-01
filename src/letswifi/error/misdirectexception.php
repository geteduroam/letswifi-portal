<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\error;

use Throwable;

class MisdirectException extends UserException
{
	public function __construct( public readonly string $httpHost, Throwable $previous )
	{
		parent::__construct( "Hostname {$httpHost} is not served by this server.", 421, $previous );
	}
}
