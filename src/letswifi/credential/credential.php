<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\credential;

use DateTimeInterface;
use JsonSerializable;
use letswifi\profile\Realm;

/**
 * @template T Credential type
 */
abstract class Credential implements JsonSerializable
{
	/**
	 * @param Closure():void $revoke
	 */
	public function __construct(
		public readonly ?string $credentialId,
		public readonly string $userId,
		public readonly ?string $clientId,
		public readonly ?string $grantSid,
		public readonly ?string $ip,
		public readonly ?string $userAgent,
		public readonly Realm $realm,
	) {
	}

	public function jsonSerialize(): array
	{
		return [
			'credential_id' => $this->credentialId,
			'not_before' => $this->getIssued(),
			'not_after' => $this->getExpiry(),
			'revoked' => $this->getRevoked(),
			'requester' => [
				'user_id' => $this->userId,
				'grant_sid' => $this->grantSid,
				'ip' => $this->ip,
				'user_agent' => $this->userAgent,
			],
			'realm' => $this->realm,
		];
	}

	abstract public function isRevoked(): bool;

	abstract public function getRevoked(): ?DateTimeInterface;

	abstract public function getIssued(): DateTimeInterface;

	abstract public function getExpiry(): ?DateTimeInterface;

	/** @return T */
	abstract public function getPayload();

	abstract public function getAnonymousIdentity(): ?string;
}
