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
use DateTimeZone;
use DomainException;
use Generator;
use PDO;
use fyrkat\openssl\PKCS12;
use letswifi\configuration\ConfigurationException;
use letswifi\tenant\Realm;
use letswifi\tenant\TenantConfig;

/**
 * @extends CredentialLog<PKCS12>
 *
 * @psalm-type statistics = array{first_issued:?\DateTimeImmutable,last_issued:?\DateTimeImmutable,first_expires:?\DateTimeImmutable,last_expires:?\DateTimeImmutable,count:int,...}
 *
 * @internal
 */
class CertificateCredentialLog extends CredentialLog
{
	public const DATE_FORMAT = 'Y-m-d H:i:s';

	private ?PDO $pdo = null;

	public function createCredentialIssuer( Realm $realm ): CertificateCredentialIssuer
	{
		// TODO: Check if this assertion can ever be true
		\assert( $this->user->canUseRealm( $realm ) );

		return new CertificateCredentialIssuer(
			user: $this->user,
			realm: $realm,
			provider: $this->provider,
			now: $this->now,
			pdo: $this->getPDO(),
			config: $this->config,
			revoke: fn( string $sub ) => $this->revoke( $realm, $sub ),
		);
	}

	/**
	 * @return array<string,statistics> Mapping from realm_id to statistics
	 *
	 * @deprecated Not currently in use, might be useful for a dashboard
	 */
	public function getStatisticsPerRealm(): array
	{
		$statement = $this->getPDO()->prepare( 'SELECT `realm` AS `realm_id`, MIN(`issued`) AS `first_issued`, MAX(`issued`) AS `last_issued`, MIN(`expires`) AS `first_expires`, MAX(`expires`) AS `last_expires`, COUNT(*) AS `count` FROM `realm_signing_log` WHERE `requester` = :requester AND `expires` > :now GROUP BY `realm`' );
		$statement->bindValue( 'now', \gmdate( static::DATE_FORMAT, $this->now->getTimestamp() ), PDO::PARAM_STR );
		$statement->bindValue( 'requester', $this->user->userId, PDO::PARAM_STR );

		$statement->execute();
		$result = [];
		while ( $row = $statement->fetch( PDO::FETCH_ASSOC ) ) {
			$result[$row['realm_id']] = [
				'realm_id' => $row['realm_id'],
				'first_issued' => static::dateTimeFromGmt( $row['first_issued'] ),
				'last_issued' => static::dateTimeFromGmt( $row['last_issued'] ),
				'first_expires' => static::dateTimeFromGmt( $row['first_expires'] ),
				'last_expires' => static::dateTimeFromGmt( $row['last_expires'] ),
				'count' => (int)$row['count'],
			];
		}

		return $result;
	}

	/**
	 * @return array<string,statistics> Mapping from client_id to statistics
	 *
	 * @deprecated Not currently in use, might be useful for a dashboard
	 */
	public function getStatisticsPerClient( Realm $realm ): array
	{
		// TODO: Check if this assertion can ever be true
		\assert( $this->user->canUseRealm( $realm ) );

		$statement = $this->getPDO()->prepare( 'SELECT `client` AS `client_id`, MIN(`issued`) AS `first_issued`, MAX(`issued`) AS `last_issued`, MIN(`expires`) AS `first_expires`, MAX(`expires`) AS `last_expires`, COUNT(*) AS `count` FROM `realm_signing_log` WHERE `realm` = :realm AND `requester` = :requester AND `expires` > :now GROUP BY `client_id`' );
		$statement->bindValue( 'now', \gmdate( static::DATE_FORMAT, $this->now->getTimestamp() ), PDO::PARAM_STR );
		$statement->bindValue( 'realm', $realm->realmId, PDO::PARAM_STR );
		$statement->bindValue( 'requester', $this->user->userId, PDO::PARAM_STR );

		$statement->execute();
		$result = [];
		while ( $row = $statement->fetch( PDO::FETCH_ASSOC ) ) {
			$result[$row['client_id']] = [
				'client_id' => $row['client_id'],
				'first_issued' => static::dateTimeFromGmt( $row['first_issued'] ),
				'last_issued' => static::dateTimeFromGmt( $row['last_issued'] ),
				'first_expires' => static::dateTimeFromGmt( $row['first_expires'] ),
				'last_expires' => static::dateTimeFromGmt( $row['last_expires'] ),
				'count' => (int)$row['count'],
			];
		}

		return $result;
	}

	/**
	 * @param string $client Client ID to filter, all clients if null
	 *
	 * @return Generator<CertificateCredential>
	 */
	public function listCredentials( ?Realm $realm = null, ?string $client = null ): Generator
	{
		// TODO: Check if this assertion can ever be true
		\assert( null === $realm || $this->user->canUseRealm( $realm ) );

		$realms = [];
		$clientQueryPart = null === $client ? '' : 'AND `client` = :client';
		$realmQueryPart = null === $realm ? '' : 'AND `realm` = :realm';
		$statement = $this->getPDO()->prepare( "SELECT `realm`, `ca_sub`, `requester`, `usage`, `sub`, `issued`, `expires`, `revoked`, `csr`, `client`, `user_agent`, `ip`, `x509` FROM `realm_signing_log` WHERE `requester` = :requester {$clientQueryPart} {$realmQueryPart} AND `expires` > :now AND revoked IS NULL ORDER BY `issued` ASC" );
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
		$tenantConfig = new TenantConfig( $this->config );
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

			yield new CertificateCredential(
				credentialId: \substr( $row['sub'], 3 ), // DANGEROUS, expect subject has only commonName
				user: $this->user,
				realm: $realm,
				provider: $this->provider,
				revoke: fn() => $this->revoke( $realm, $row['sub'] ),
				issued: $this->dateTimeFromGmt( $row['issued'] ) ?? throw new DomainException( 'Issued cannot be NULL' ),
				expiry: $this->dateTimeFromGmt( $row['expires'] ) ?? throw new DomainException( 'Expires cannot be NULL' ),
				revoked: $this->dateTimeFromGmt( $row['revoked'] ),
				identity: \substr( $row['sub'], 3 ), // DANGEROUS, expect subject has only commonName
			);
		}
	}

	public function getCredential( string $credentialId, ?Realm $realm = null, ?string $client = null ): CertificateCredential
	{
		// TODO: Check if this assertion can ever be true
		\assert( null === $realm || $this->user->canUseRealm( $realm ) );

		$clientQueryPart = null === $client ? '' : 'AND `client` = :client';
		$realmQueryPart = null === $realm ? '' : 'AND `realm` = :realm';
		$statement = $this->getPDO()->prepare( "SELECT `realm`, `ca_sub`, `requester`, `usage`, `sub`, `issued`, `expires`, `revoked`, `csr`, `client`, `user_agent`, `ip`, `x509` FROM `realm_signing_log` WHERE `sub` =:sub AND `requester` = :requester {$clientQueryPart} {$realmQueryPart} ORDER BY `issued` ASC" );
		$statement->bindValue( 'sub', $credentialId, PDO::PARAM_STR );
		$statement->bindValue( 'requester', $this->user->userId, PDO::PARAM_STR );
		if ( null !== $client ) {
			$statement->bindParam( 'client', $client, PDO::PARAM_STR );
		}
		if ( null !== $realm ) {
			$statement->bindValue( 'realm', $realm->realmId, PDO::PARAM_STR );
		}

		$statement->execute();
		$tenantConfig = new TenantConfig( $this->config );
		if ( $row = $statement->fetch( PDO::FETCH_ASSOC ) ) {
			$realm = $tenantConfig->getRealm( $row['realm'] );

			return new CertificateCredential(
				credentialId: $row['sub'],
				user: $this->user,
				realm: $realm,
				provider: $this->provider,
				revoke: fn() => $this->revoke( $realm, $row['sub'] ),
				issued: $this->dateTimeFromGmt( $row['issued'] ) ?? throw new DomainException( 'Expires cannot be NULL' ),
				expiry: $this->dateTimeFromGmt( $row['expires'] ) ?? throw new DomainException( 'Expires cannot be NULL' ),
				revoked: $this->dateTimeFromGmt( $row['revoked'] ),
				identity: \substr( $row['sub'], 3 ), // DANGEROUS, expect subject has only commonName
			);
		}

		throw new DomainException( 'Credential does not exist' );
	}

	protected static function dateTimeFromGmt( ?string $datetimeGmt ): ?DateTimeImmutable
	{
		$result = false;
		if ( null !== $datetimeGmt ) {
			$gmt = new DateTimeZone( 'GMT' );
			$result = DateTimeImmutable::createFromFormat( static::DATE_FORMAT, $datetimeGmt, $gmt );
		}

		return $result ?: null;
	}

	private function revoke( Realm $realm, string $subject ): void
	{
		// TODO: Check if this assertion can ever be true
		\assert( $this->user->canUseRealm( $realm ) );

		$revokeStatement = $this->getPDO()->prepare( 'UPDATE `realm_signing_log` SET `revoked` = :revoked WHERE `sub` = :sub AND `realm` = :realm AND `requester` = :requester AND `revoked` IS NULL' );
		$revokeStatement->bindValue( 'revoked', \gmdate( static::DATE_FORMAT, $this->now->getTimestamp() ), PDO::PARAM_STR );
		$revokeStatement->bindValue( 'requester', $this->user->userId, PDO::PARAM_STR );
		$revokeStatement->bindValue( 'realm', $realm->realmId, PDO::PARAM_STR );
		$revokeStatement->bindParam( 'sub', $subject, PDO::PARAM_STR );
		$revokeStatement->execute();
	}

	private function getPDO(): PDO
	{
		if ( null === $this->pdo ) {
			$providers = $this->config->getDictionary( 'provider' );
			$pdoData = $providers->getDictionary( $this->provider->host )->getDictionary( 'pdo' );
			$dsn = $pdoData->getString( 'dsn' );
			$username = $pdoData->getStringOrNull( 'username' );
			$password = $pdoData->getStringOrNull( 'password' );

			$this->pdo = new PDO( $dsn, $username, $password );
			$this->pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		}

		return $this->pdo;
	}
}
