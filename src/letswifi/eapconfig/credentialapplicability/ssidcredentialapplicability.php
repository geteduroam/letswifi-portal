<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2020, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\EapConfig\CredentialApplicability;

class SSIDCredentialApplicability implements ICredentialApplicability
{
	/** @var string */
	private $ssid;

	/** @var string */
	private $minRSNProto;

	public function __construct( string $ssid, string $minRSNProto = 'CCMP' )
	{
		$this->ssid = $ssid;
		$this->minRSNProto = $minRSNProto;
	}

	public function generateEapConfigXml(): string
	{
		return ''
			. "\r\n\t\t\t" . '<IEEE80211>'
			. "\r\n\t\t\t\t" . '<SSID>' . static::e( $this->ssid ) . '</SSID>'
			. "\r\n\t\t\t\t" . '<MinRSNProto>' . static::e( $this->minRSNProto ) . '</MinRSNProto>'
			. "\r\n\t\t\t" . '</IEEE80211>'
			;
	}

	private static function e( string $s ): string
	{
		return \htmlspecialchars( $s, \ENT_QUOTES, 'UTF-8' );
	}
}
