<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\credential;

use DateTimeImmutable;
use DomainException;
use Generator;
use letswifi\LetsWifiConfig;
use letswifi\auth\User;
use letswifi\tenant\Provider;
use letswifi\tenant\Realm;

/**
 * @template T
 */
abstract class CredentialLog
{
	public function __construct(
		public readonly User $user,
		public readonly Provider $provider,
		protected readonly LetsWifiConfig $config,
		public readonly DateTimeImmutable $now = new DateTimeImmutable(),
	) {
	}

	/**
	 * @return CredentialIssuer<T>
	 */
	final public function getCredentialIssuer( string|Realm $realm ): CredentialIssuer
	{
		// Before returning, we make sure that the realm fits with the current provider and current user

		// Ensure that the realm is one that is available for this user
		$realm = $this->user->getRealm( \is_string( $realm ) ? $realm : $realm->realmId );

		// This should always pass if $provider->getAuthenticatedUser() was called
		if ( !$this->provider->hasRealm( $realm ) ) {
			throw new DomainException( "Realm {$realm->realmId} is not valid for the current provider." );
		}

		if ( !$this->user->canUseRealm( $realm ) ) {
			throw new DomainException( "Realm {$realm->realmId} cannot be used by user {$this->user->userId}." );
		}

		return $this->createCredentialIssuer( $realm );
	}

	/**
	 * @return CredentialIssuer<T>
	 */
	abstract public function createCredentialIssuer( Realm $realm ): CredentialIssuer;

	/**
	 * @return Generator<Credential<T>>
	 */
	abstract public function listCredentials( ?Realm $realm = null ): Generator;

	abstract public function getCredential( string $credentialId, ?Realm $realm = null, ?string $client = null ): CertificateCredential;
}
