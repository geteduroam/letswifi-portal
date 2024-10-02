<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\browserauth;

use RuntimeException;

class HomeOrgMismatchException extends MismatchIdpException
{
	/** @var array<string>|string */
	private $required;

	/** @var array */
	private $provided;

	/** @var ?string $ */
	private $username;

	/**
	 * @param array<string>|string $required
	 */
	public function __construct( $required, array $provided, ?string $username = null )
	{
		if ( \is_array( $required ) && 1 === \count( $required ) ) {
			$required = \reset( $required );
		}

		$this->required = $required;
		$this->provided = $provided;
		$this->username = $username;

		$requiredStr = \is_string( $required )
			? \sprintf( "'%s'", $required )
			: \sprintf( '[%s]', \implode( ', ', $required ) );
		$providedStr = 1 === \count( $provided )
			? \sprintf( "'%s'", \reset( $provided ) )
			: \sprintf( '[%s]', \implode( ', ', $provided ) );
		RuntimeException::__construct( "Cannot reconsiliate {$requiredStr} with {$providedStr}" . ( isset( $username ) ? " with username {$username}" : '' ) );
	}
}
