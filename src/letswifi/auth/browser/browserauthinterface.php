<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
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
	 *
	 * If the user is not logged in, output a login page or redirect to SSO service
	 *
	 * Function does not return if user is not logged in,
	 * if this is not intended, use #getUserId() instead
	 *
	 * @return string User ID
	 */
	public function requireAuth(): string;

	/**
	 * Get the current user ID
	 *
	 * If the user is not logged in, return null
	 * Return value is the same as #requireAuth() if logged in.
	 *
	 * @return ?string User ID if logged in, null otherwise
	 */
	public function getUserId(): ?string;

	/**
	 * @psalm-assert-if-true string $this->getUserId()
	 *
	 * @psalm-assert-if-false never $this->requireAuth()
	 */
	public function isLoggedIn(): bool;

	/**
	 * Get the URL to logout the user
	 *
	 * @param $redirect URL to redirect to after logging out, if null redirect url is decided by the authentication system, it may redirect back where you came from
	 */
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
