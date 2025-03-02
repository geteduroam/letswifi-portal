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
use letswifi\LetsWifiApp;

class NotFoundException extends UserException
{
	public readonly string $url;

	public function __construct( ?string $url = null, ?Throwable $previous = null )
	{
		$this->url = $url ?? $_SERVER['REQUEST_URI'] ?? LetsWifiApp::getRequestPath();
		parent::__construct( "The requested URL {$this->url} was not found.", 404, $previous );
	}
}
