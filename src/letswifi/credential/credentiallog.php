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
use DomainException;
use Generator;
use letswifi\auth\User;
use letswifi\configuration\Dictionary;
use letswifi\error\RealmMismatchException;
use letswifi\profile\Provider;
use letswifi\profile\Realm;

/**
 * @template T
 */
abstract class CredentialLog
{
	public function __construct(
		public readonly User $user,
		public readonly Provider $provider,
		protected readonly Dictionary $config,
		public readonly DateTimeImmutable $now = new DateTimeImmutable(),
	) {
		// TODO: Do we really need the provider here,
		// it's already in the user object which we should be able to trust here
		if ( $this->provider->host !== $this->user->provider->host ) {
			throw new DomainException( 'The provider must match the user provider' );
		}
	}

	/**
	 * @return CredentialIssuer<T>
	 */
	final public function getCredentialIssuer( string|Realm $realm ): CredentialIssuer
	{
		// Before returning, we make sure that the realm fits with the current provider and current user

		// Ensure that the realm is one that is available for this user
		$realm = $this->user->getRealm( \is_string( $realm ) ? $realm : $realm->realmId );

		// Should always pass; we just got the realm object checked by $this->user->getRealm()
		\assert( $this->user->canUseRealm( $realm ), "User {$this->user->userId} cannot use realm {$realm->realmId}" );

		// TODO: We have our own $this->provider, but see the TODO in our constructor
		// in any case both refer to the same provider
		if ( !$this->user->provider->hasRealm( $realm ) ) {
			throw new RealmMismatchException( $realm, provider: $this->user->provider );
		}

		return $this->createCredentialIssuer( $realm );
	}

	/**
	 * @return Generator<Credential<T>>
	 */
	abstract public function listCredentials( ?Realm $realm = null ): Generator;

	/**
	 * @return Credential<T>
	 */
	abstract public function getCredential( string $credentialId, ?Realm $realm = null, ?string $client = null ): Credential;

	public function revokeCredential( string $credentialId ): void
	{
		$credential = $this->getCredential( $credentialId );
		$credential->revoke();
	}

	/**
	 * @return CredentialIssuer<T>
	 */
	abstract protected function createCredentialIssuer( Realm $realm ): CredentialIssuer;
}
