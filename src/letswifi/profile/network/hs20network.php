<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\profile\network;

class HS20Network implements Network
{
	/** @var string */
	private $consortiumOID;

	public function __construct( string $consortiumOID )
	{
		$this->consortiumOID = $consortiumOID;
	}

	public function getConsortiumOID(): string
	{
		return $this->consortiumOID;
	}
}
