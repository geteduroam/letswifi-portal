<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\profile;

use letswifi\profile\network\Network;

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
	 * @return array<Network>
	 */
	public function getNetworks(): array
	{
		return $this->data['credentialApplicability'] ?? [];
	}

	public function getDisplayName(): string
	{
		return $this->displayName;
	}

	public function getDescription(): ?string
	{
		return $this->data['description'] ?? null;
	}

	public function getProviderLocation(): ?Location
	{
		return $this->data['location'] ?? null;
	}

	public function getProviderLogo(): ?Logo
	{
		return $this->data['logo'] ?? null;
	}

	public function getTermsOfUse(): ?string
	{
		return $this->data['terms'] ?? null;
	}

	public function getHelpDesk(): ?Helpdesk
	{
		return $this->data['helpdesk'] ?? null;
	}
}
