<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2022, Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: 2020-2022, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\browserauth;

interface BrowserAuthInterface
{
	/**
	 * Ensure that the user is logged in
	 * Outputs a login page or redirects to an SSO service, and exits
	 *
	 * @return string User ID
	 */
	public function requireAuth(): string;

	public function getLogoutURL( ?string $redirect = null ): ?string;

	/**
	 * Get a custom realm that overrides the default realm. May start with a dot for realm prefix
	 *
	 * For example, if the realm is example.com and this function returns "student",
	 * the final realm must be student.example.com; a dot has to be added,
	 * unless that would cause the realm to contain "@.""
	 *
	 * @return ?string The prefix to add to the realm, do not add anything if null
	 */
	public function getRealm(): ?string;
}
