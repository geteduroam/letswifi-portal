<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\credential;

use DomainException;
use Generator;
use PDO;
use fyrkat\openssl\PKCS12;
use letswifi\configuration\ConfigurationException;
use letswifi\profile\ProfileConfig;
use letswifi\profile\Realm;

/**
 * @extends CredentialLog<PKCS12>
 *
 * @psalm-type statistics = array{first_issued:?\DateTimeImmutable,last_issued:?\DateTimeImmutable,first_expires:?\DateTimeImmutable,last_expires:?\DateTimeImmutable,count:int,...}
 *
 * @internal
 */
class CertificateCredentialLog extends CredentialLog
{
	use Database;
	use GMT;

	public const DATE_FORMAT = 'Y-m-d H:i:s';

	/**
	 * @param string $client Client ID to filter, all clients if null
	 *
	 * @return Generator<CertificateCredential>
	 */
	public function listCredentials( ?Realm $realm = null, ?string $client = null ): Generator
	{
		// TODO: Check if this assertion can ever fail;
		// maybe convert to if/throw
		\assert( null === $realm || $this->user->canUseRealm( $realm ) );

		$realms = [];
		$clientQueryPart = null === $client ? '' : 'AND client = :client';
		$realmQueryPart = null === $realm ? '' : 'AND realm = :realm';
		$statement = $this->getPDO()->prepare( <<<SQL
			SELECT realm, ca_sub, requester, ident, "grant", "usage", sub, issued, expires, revoked, csr, client, user_agent, ip, x509
			FROM realm_signing_log
			WHERE requester = :requester
				AND expires > :now
				AND revoked IS NULL
				{$clientQueryPart}
				{$realmQueryPart}
			ORDER BY issued ASC
			SQL );
		$statement->bindValue( 'requester', $this->user->userId, PDO::PARAM_STR );
		$statement->bindValue( 'now', \gmdate( static::DATE_FORMAT, $this->now->getTimestamp() ), PDO::PARAM_STR );
		if ( null !== $client ) {
			$statement->bindParam( 'client', $client, PDO::PARAM_STR );
		}
		if ( null !== $realm ) {
			$statement->bindValue( 'realm', $realm->realmId, PDO::PARAM_STR );
			$realms[$realm->realmId] = $realm;
		}

		$statement->execute();
		$tenantConfig = new ProfileConfig( $this->config );
		while ( $row = $statement->fetch( PDO::FETCH_ASSOC ) ) {
			try {
				$realm = \array_key_exists( $row['realm'], $realms )
				? $realms[$row['realm']]
				: $realms[$row['realm']] = $tenantConfig->getRealm( $row['realm'] );
			} catch ( ConfigurationException $_ ) {
				// If the realm was removed, but there are lingering credentials
				$realm = $realms[$row['realm']] = null;
				// Do not return these records
				// TODO: Do we want to create a fake realm object so we can still show these records?
				continue;
			}

			if ( null === $realm ) {
				continue;
			}

			$issued = $this->dateTimeFromGmt( $row['issued'] );
			$expiry = $this->dateTimeFromGmt( $row['expires'] );
			$revoked = $this->dateTimeFromGmt( $row['revoked'] );
			\assert( null !== $issued );
			\assert( null !== $expiry );

			yield $row['ident'] => new CertificateCredential(
				credentialId: $row['ident'],
				userId: $row['requester'],
				clientId: $row['client'],
				grantSid: $row['grant'],
				ip: $row['ip'],
				userAgent: $row['user_agent'],
				realm: $realm,
				provider: $this->provider,
				revoke: fn() => $this->revokeCredential( $row['ident'] ),
				issued: $issued,
				expiry: $expiry,
				revoked: $revoked,
			);
		}
	}

	public function getCredential( string $credentialId, ?Realm $realm = null, ?string $client = null ): CertificateCredential
	{
		// TODO: Check if this assertion can ever fail;
		// maybe convert to if/throw
		\assert( null === $realm || $this->user->canUseRealm( $realm ) );

		$clientQueryPart = null === $client ? '' : 'AND client = :client';
		$realmQueryPart = null === $realm ? '' : 'AND realm = :realm';
		$statement = $this->getPDO()->prepare( <<<SQL
			SELECT realm, requester, ident, "grant", ca_sub, usage, sub, issued, expires, revoked, csr, client, user_agent, ip, x509, ident
			FROM realm_signing_log
			WHERE
				ident = :ident
				AND requester = :requester
				{$clientQueryPart}
				{$realmQueryPart}
			ORDER BY issued ASC
			SQL );
		$statement->bindValue( 'ident', $credentialId, PDO::PARAM_STR );
		$statement->bindValue( 'requester', $this->user->userId, PDO::PARAM_STR );
		if ( null !== $client ) {
			$statement->bindParam( 'client', $client, PDO::PARAM_STR );
		}
		if ( null !== $realm ) {
			$statement->bindValue( 'realm', $realm->realmId, PDO::PARAM_STR );
		}

		$statement->execute();
		$tenantConfig = new ProfileConfig( $this->config );
		if ( $row = $statement->fetch( PDO::FETCH_ASSOC ) ) {
			$realm = $tenantConfig->getRealm( $row['realm'] );
			$issued = $this->dateTimeFromGmt( $row['issued'] );
			$expiry = $this->dateTimeFromGmt( $row['expires'] );
			$revoked = $this->dateTimeFromGmt( $row['revoked'] );
			\assert( null !== $issued );
			\assert( null !== $expiry );

			return new CertificateCredential(
				credentialId: $row['ident'],
				userId: $row['requester'],
				clientId: $row['client'],
				grantSid: $row['grant'],
				ip: $row['ip'],
				userAgent: $row['user_agent'],
				realm: $realm,
				provider: $this->provider,
				revoke: fn() => $this->revokeCredential( $row['ident'] ),
				issued: $issued,
				expiry: $expiry,
				revoked: $revoked,
			);
		}

		throw new DomainException( 'Credential does not exist' );
	}

	public function revokeCredential( string $credentialId ): void
	{
		$revokeStatement = $this->getPDO()->prepare( <<<SQL
			UPDATE realm_signing_log
			SET revoked = :revoked
			WHERE
				ident = :ident
				AND requester = :requester
				AND revoked IS NULL
			SQL );
		$revokeStatement->bindValue( 'revoked', \gmdate( static::DATE_FORMAT, $this->now->getTimestamp() ), PDO::PARAM_STR );
		$revokeStatement->bindParam( 'ident', $credentialId, PDO::PARAM_STR );
		$revokeStatement->bindValue( 'requester', $this->user->userId, PDO::PARAM_STR );
		$revokeStatement->execute();
	}

	public function getCredentialAdministrator(): CredentialAdmin
	{
		return new CertificateCredentialAdmin(
			admin: $this->user->promote(),
			provider: $this->provider,
			config: $this->config,
			now: $this->now,
		);
	}

	protected function createCredentialIssuer( Realm $realm ): CertificateCredentialIssuer
	{
		return new CertificateCredentialIssuer(
			user: $this->user,
			realm: $realm,
			provider: $this->provider,
			now: $this->now,
			pdo: $this->getPDO(),
			config: $this->config,
			revoke: fn( string $ident ) => $this->revokeCredential( $ident ),
		);
	}
}
