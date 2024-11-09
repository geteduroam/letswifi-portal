<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\auth\browser;

interface BrowserAuthInterface
{
	/**
	 * Ensure that the user is logged in
	 * Outputs a login page or redirects to an SSO service, and exits
	 *
	 * @return string User ID
	 */
	public function requireAuth(): string;

	public function getUserId(): ?string;

	public function getLogoutURL( ?string $redirect = null ): ?string;

	/**
	 * Get the affiliations for the user
	 *
	 * This is used to select the realm that is used.
	 *
	 * @return array<string> List of affiliations
	 */
	public function getAffiliations(): array;
}
