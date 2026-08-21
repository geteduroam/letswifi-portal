<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\credential;

use DateTimeImmutable;
use DateTimeInterface;
use DomainException;
use Generator;
use letswifi\auth\Admin;
use letswifi\profile\ProfileService;
use letswifi\profile\Provider;
use letswifi\profile\Realm;

abstract class CredentialAdmin
{
	public function __construct(
		public readonly Admin $admin,
		public readonly Provider $provider,
		protected readonly ProfileService $profileService,
		public readonly DateTimeImmutable $now = new DateTimeImmutable(),
	) {
		// TODO: Do we really need the provider here,
		// it's already in the user object which we should be able to trust here
		if ( $this->provider->host !== $this->admin->provider->host ) {
			throw new DomainException( 'The provider must match the user provider' );
		}
	}

	/**
	 * @param array<Realm|string> $realms    Only return from these realms, if empty all available realms are used
	 * @param ?string             $requester Filter requester
	 * @param ?DateTimeInterface  $validOn   Consider credentials that are valid on this point in time
	 *
	 * @return Generator<string,RequesterAggregate>
	 */
	abstract public function listRequesters( array $realms = [], ?string $requester = null, ?DateTimeInterface $validOn = null ): Generator;

	/**
	 * @param array<Realm|string> $realms        Only return from these realms, if empty all available realms are used
	 * @param ?string             $requester     Filter requester
	 * @param ?DateTimeInterface  $validOn       Consider credentials that are valid on this point in time
	 * @param bool                $unrevokedOnly Only return credentials that are not revoked
	 *
	 * @return Generator<string,Credential>
	 */
	abstract public function listCredentials( array $realms = [], ?string $requester = null, ?DateTimeInterface $validOn = null, bool $unrevokedOnly = false ): Generator;

	/**
	 * List all credentials that are signed by the specified CA, including revoked credentials
	 *
	 * @param string             $ca
	 * @param ?string            $requester Filter requester
	 * @param ?DateTimeInterface $validOn   Consider credentials that are valid on this point in time
	 *
	 * @return Generator<string,Credential>
	 */
	abstract public function listCredentialsBySigner( string $signingCa, ?DateTimeInterface $validOn = null ): Generator;

	/**
	 * @param string              $ident
	 * @param array<Realm|string> $realms Only return from these realms, if empty all available realms are used
	 */
	abstract public function getCredential( string $ident, array $realms = [] ): ?Credential;

	/**
	 * @param string              $credentialId
	 * @param array<Realm|string> $realms       Only revoke credentials within these realms
	 * @param ?string             $requester    Only revoke credentials beloging to requester
	 */
	abstract public function revokeCredential( string $credentialId, array $realms = [], ?string $requester = null ): void;

	/**
	 * Revoke all credentials matching the query
	 *
	 * @param string              $requester Only revoke credentials beloging to requester
	 * @param array<Realm|string> $realms    Only revoke credentials within these realms
	 * @param ?DateTimeInterface  $validOn   Consider credentials that are valid on this point in time
	 */
	abstract public function revokeRequester( string $requester, array $realms = [], ?DateTimeInterface $validOn = null ): void;

	/**
	 * Get statistics for the provided realms
	 *
	 * @param array<Realm|string> $realms  Realms to get statistics for
	 * @param ?DateTimeInterface  $validOn Consider credentials that are valid on this point in time
	 *
	 * @return Generator<string,array{realm:string,earliest_valid:DateTimeInterface,last_valid:DateTimeInterface,total_accounts:int,valid_accounts:int,total_requesters:int}>
	 */
	abstract public function getRealmStats( array $realms = [], ?DateTimeInterface $validOn = null ): Generator;
}
