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

interface IProfileData
{
	public function getRealm(): string;

	public function getLanguageCode(): string;

	/**
	 * @return array<Network>
	 */
	public function getNetworks(): array;

	public function getDisplayName(): string;

	public function getDescription(): ?string;

	public function getProviderLocation(): ?Location;

	public function getProviderLogo(): ?Logo;

	public function getTermsOfUse(): ?string;

	public function getHelpDesk(): ?Helpdesk;
}
