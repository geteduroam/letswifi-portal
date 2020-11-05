<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2020, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
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

	public function generateEapConfigXml(): string
	{
		return ''
			. "\r\n\t\t\t" . '<IEEE80211>'
			. "\r\n\t\t\t\t" . '<ConsortiumOID>' . static::e( $this->consortiumOID ) . '</ConsortiumOID>'
			. "\r\n\t\t\t" . '</IEEE80211>'
			;
	}

	private static function e( string $s ): string
	{
		return \htmlspecialchars( $s, \ENT_QUOTES, 'UTF-8' );
	}
}
