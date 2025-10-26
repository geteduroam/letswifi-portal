<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\auth;

use LogicException;
use letswifi\profile\Provider;

class Admin extends User
{
	protected function __construct(
		string $userId,
		Provider $provider,
		array $realms,
		array $affiliations,
		?string $clientId = null,
		?string $grantSid = null,
		?string $ip = null,
		?string $userAgent = null,
	) {
		parent::__construct(
			$userId,
			$provider,
			$realms,
			$affiliations,
			$clientId,
			$grantSid,
			$ip,
			$userAgent,
		);
	}

	public function jsonSerialize(): array
	{
		return ['admin' => true] + parent::jsonSerialize();
	}

	public function promote(): never
	{
		throw new LogicException( 'Attempted to promote admin to admin' );
	}
}
