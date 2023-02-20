<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2022, Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: 2020-2022, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\profile;

use letswifi\profile\network\HS20Network;
use letswifi\profile\network\Network;
use letswifi\profile\network\SSIDNetwork;

class EduroamProfileData extends AbstractProfileData
{
	public function __construct( string $realm, string $displayName = 'eduroam', array $data = [], string $languageCode = 'en' )
	{
		parent::__construct( $realm, $displayName, $data, $languageCode );
	}

	/**
	 * @return array<Network>
	 */
	public function getNetworks(): array
	{
		$result = parent::getNetworks();
		$result[] = new HS20Network( '001bc50460' );
		$result[] = new HS20Network( '5a03ba0000' );
		$result[] = new HS20Network( '5a03ba0800' );
		$result[] = new SSIDNetwork( 'eduroam' );

		return $result;
	}
}
