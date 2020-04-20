<?php declare(strict_types=1);

/*
 * This file is part of geteduroam; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2020, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace geteduroam\EapConfig\Profile;

use geteduroam\EapConfig\CredentialApplicability\HS20CredentialApplicability;
use geteduroam\EapConfig\CredentialApplicability\ICredentialApplicability;
use geteduroam\EapConfig\CredentialApplicability\SSIDCredentialApplicability;

class EduroamProfileData extends AbstractProfileData
{
	public function __construct( string $realm, string $displayName = 'eduroam', array $data = [], string $languageCode = 'en' )
	{
		parent::__construct( $realm, $displayName, $data, $languageCode );
	}

	/**
	 * @return array<ICredentialApplicability>
	 */
	public function getCredentialApplicabilities(): array
	{
		$result = parent::getCredentialApplicabilities();
		$result[] = new HS20CredentialApplicability( '001bc50460' );
		$result[] = new SSIDCredentialApplicability( 'eduroam' );

		return $result;
	}
}
