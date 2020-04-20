<?php declare(strict_types=1);

/*
 * This file is part of geteduroam; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2020, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace geteduroam\EapConfig\Profile;

use geteduroam\EapConfig\CredentialApplicability\ICredentialApplicability;

class AbstractProfileData implements IProfileData
{
	/** @var array<string,mixed> */
	private $data;

	/** @var string */
	private $realm;

	/** @var string */
	private $displayName;

	/** @var string */
	private $languageCode;

	public function __construct( string $realm, string $displayName, array $data, string $languageCode = 'en' )
	{
		$this->realm = $realm;
		$this->displayName = $displayName;
		$this->data = $data;
		$this->languageCode = $languageCode;
	}

	public function getRealm(): string
	{
		return $this->realm;
	}

	public function getLanguageCode(): string
	{
		return $this->languageCode;
	}

	/**
	 * @return array<ICredentialApplicability>
	 */
	public function getCredentialApplicabilities(): array
	{
		return $this->data['credentialApplicability'] ?? [];
	}

	public function getDisplayName(): string
	{
		return $this->displayName;
	}

	public function getDescription(): ?string
	{
		return $this->data['description'];
	}

	public function getProviderLocation(): ?Location
	{
		return $this->data['location'];
	}

	public function getProviderLogo(): ?Logo
	{
		return $this->data['logo'];
	}

	public function getTermsOfUse(): ?string
	{
		return $this->data['terms'];
	}

	public function getHelpDesk(): ?Helpdesk
	{
		return $this->data['helpdesk'];
	}
}
