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

class HttpMethodException extends UserException
{
	public readonly ?string $provided;

	/**
	 * @param array<string> $allowed
	 */
	public function __construct( public readonly array $allowed, ?string $provided = null, ?Throwable $previous = null )
	{
		$this->provided = $provided ?? $_SERVER['REQUEST_METHOD'] ?? null;
		parent::__construct( "{$this->provided} request not allowed, only " . \implode( ', ', $allowed ) . ' allowed', 405, $previous );
	}
}
