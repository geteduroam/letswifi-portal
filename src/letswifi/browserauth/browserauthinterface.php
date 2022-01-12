<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2021, Jørn Åne de Jong, Uninett AS <jornane.dejong@surf.nl>
 * Copyright: 2020-2021, Paul Dekkers, SURF <paul.dekkers@surf.nl>
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

	/**
	 * Guess current realm from all configured realms
	 *
	 * @param array<string,array> $params realm.auth setting from config
	 */
	public function guessRealm( array $params ): ?string;

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
