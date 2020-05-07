<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2020, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
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
}
