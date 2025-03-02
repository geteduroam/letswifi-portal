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
use DomainException;
use JsonSerializable;
use letswifi\auth\User;
use letswifi\tenant\Provider;
use letswifi\tenant\Realm;

/**
 * @template T
 */
abstract class Credential implements JsonSerializable
{
	/**
	 * @param Closure():T $pkcs12Generator
	 */
	public function __construct(
		public readonly ?string $credentialId,
		public readonly User $user,
		public readonly Realm $realm,
		public readonly Provider $provider,
		protected readonly ?\Closure $revoke = null,
	) {
	}

	public function jsonSerialize(): array
	{
		return [
			'credential_id' => $this->credentialId,
			'not_before' => $this->getIssued(),
			'not_after' => $this->getExpiry(),
			'revoked' => $this->getRevoked(),
			'user' => $this->user,
			'realm' => $this->realm,
			'provider' => $this->provider,
		];
	}

	abstract public function isRevoked(): bool;

	abstract public function getRevoked(): ?DateTimeInterface;

	abstract public function getIssued(): ?DateTimeInterface;

	abstract public function getExpiry(): ?DateTimeInterface;

	/** @return T */
	abstract public function getPayload();

	public function revoke(): void
	{
		$f = $this->revoke;
		if ( null === $f ) {
			throw new DomainException( 'Credential cannot be revoked' );
		}
		$f();
	}

	abstract public function getIdentity(): ?string;
}
