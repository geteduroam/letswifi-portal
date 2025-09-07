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
use letswifi\auth\User;
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
	public const DATE_FORMAT = 'Y-m-d H:i:s';

	private ?PDO $pdo = null;

	/**
	 * @return array<string,statistics> Mapping from realm_id to statistics
	 *
	 * @deprecated Not currently in use, might be useful for a dashboard
	 */
	public function getStatisticsPerRealm(): array
	{
		$statement = $this->getPDO()->prepare( <<<SQL
			SELECT
				realm AS realm_id,
				MIN(issued) AS first_issued,
				MAX(issued) AS last_issued,
				MIN(expires) AS first_expires,
				MAX(expires) AS last_expires,
				COUNT(*) AS count
			FROM realm_signing_log
			WHERE
				requester = :requester
				AND expires > :now
			GROUP BY realm
			SQL );
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
		// TODO: Check if this assertion can ever fail;
		// maybe convert to if/throw
		\assert( $this->user->canUseRealm( $realm ) );

		$statement = $this->getPDO()->prepare( <<<SQL
			SELECT
				client AS client_id,
				MIN(issued) AS first_issued,
				MAX(issued) AS last_issued,
				MIN(expires) AS first_expires,
				MAX(expires) AS last_expires,
				COUNT(*) AS count
			FROM realm_signing_log
			WHERE
				realm = :realm
				AND requester = :requester
				AND expires > :now
			GROUP BY client_id
			SQL );
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
		// TODO: Check if this assertion can ever fail;
		// maybe convert to if/throw
		\assert( null === $realm || $this->user->canUseRealm( $realm ) );

		$realms = [];
		$clientQueryPart = null === $client ? '' : 'AND client = :client';
		$realmQueryPart = null === $realm ? '' : 'AND realm = :realm';
		$statement = $this->getPDO()->prepare( <<<SQL
			SELECT realm, ca_sub, requester, ident, "grant", usage, sub, issued, expires, revoked, csr, client, user_agent, ip, x509
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

			yield new CertificateCredential(
				credentialId: $row['ident'],
				user: new User(
					userId: $row['requester'],
					provider: $this->provider,
					realms: [], // We cannot use this user object to issue more realms
					affiliations: [], // We do not record affiliations at issue moment
					clientId: $row['client'],
					grantSid: $row['grant'],
					ip: $row['ip'],
					userAgent: $row['user_agent'],
				),
				realm: $realm,
				provider: $this->provider,
				revoke: fn() => $this->revokeCredential( $row['ident'] ),
				issued: $this->dateTimeFromGmt( $row['issued'] ) ?? throw new DomainException( 'Issued cannot be NULL' ),
				expiry: $this->dateTimeFromGmt( $row['expires'] ) ?? throw new DomainException( 'Expires cannot be NULL' ),
				revoked: $this->dateTimeFromGmt( $row['revoked'] ),
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

			return new CertificateCredential(
				credentialId: $row['ident'],
				user: $this->user,
				realm: $realm,
				provider: $this->provider,
				revoke: fn() => $this->revokeCredential( $row['ident'] ),
				issued: $this->dateTimeFromGmt( $row['issued'] ) ?? throw new DomainException( 'Expires cannot be NULL' ),
				expiry: $this->dateTimeFromGmt( $row['expires'] ) ?? throw new DomainException( 'Expires cannot be NULL' ),
				revoked: $this->dateTimeFromGmt( $row['revoked'] ),
			);
		}

		throw new DomainException( 'Credential does not exist' );
	}

	public function revokeCredential( string $credentialId ): void
	{
		// Explicitly do not check the realm here,
		// as the user may have lost access to the realm but still have active credentials
		// We DO check that the requester is the current user

		$revokeStatement = $this->getPDO()->prepare( <<<SQL
			UPDATE realm_signing_log
			SET revoked = :revoked
			WHERE
				ident = :ident
				AND requester = :requester
				AND revoked IS NULL
			SQL );
		$revokeStatement->bindValue( 'revoked', \gmdate( static::DATE_FORMAT, $this->now->getTimestamp() ), PDO::PARAM_STR );
		$revokeStatement->bindValue( 'requester', $this->user->userId, PDO::PARAM_STR );
		$revokeStatement->bindParam( 'ident', $credentialId, PDO::PARAM_STR );
		$revokeStatement->execute();
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

	protected static function dateTimeFromGmt( ?string $datetimeGmt ): ?DateTimeImmutable
	{
		$result = false;
		if ( null !== $datetimeGmt ) {
			$gmt = new DateTimeZone( 'GMT' );
			$result = DateTimeImmutable::createFromFormat( static::DATE_FORMAT, $datetimeGmt, $gmt );
		}

		return $result ?: null;
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

			if ( \strstr( $dsn, ':', true ) === 'mysql' ) {
				// https://dev.mysql.com/doc/refman/8.4/en/set-variable.html
				// https://dev.mysql.com/doc/refman/8.4/en/sql-mode.html#sqlmode_ansi_quotes
				$this->pdo->exec( 'SET sql_mode = \'ANSI_QUOTES\';' );
			}
		}

		return $this->pdo;
	}
}
