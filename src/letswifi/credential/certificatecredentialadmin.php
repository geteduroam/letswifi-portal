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
use Generator;
use PDO;
use letswifi\error\RealmMismatchException;
use letswifi\profile\Realm;

/**
 * @internal
 */
class CertificateCredentialAdmin extends CredentialAdmin
{
	use UTC;

	public function listRequesters( array $realms = [], ?string $requester = null, ?DateTimeInterface $validOn = null ): Generator
	{
		if ( null === $validOn ) {
			$validOn = $this->now;
		}
		$pdo = $this->profileService->getPDO();
		$extraConditions = $this->getRealmConditions( $realms, static fn( $s ) => $pdo->quote( $s ) );
		if ( null !== $requester ) {
			$extraConditions .= ' AND requester = :requester';
		}
		$stmt = $pdo->prepare( <<<SQL
				SELECT
					requester, realm,
					MIN(issued) earliest_valid,
					MAX(expires) last_valid,
					COUNT("serial") total_accounts,
					COUNT(CASE WHEN revoked IS NULL THEN "serial" END) valid_accounts
				FROM realm_signing_log
				WHERE expires > :valid_on AND issued < :valid_on
					{$extraConditions}
				GROUP BY requester, realm
				ORDER BY issued DESC;
			SQL );
		$stmt->bindValue( 'valid_on', $this->formatUtc( $validOn ), PDO::PARAM_STR );
		if ( null !== $requester ) {
			$stmt->bindValue( 'requester', $requester, PDO::PARAM_STR );
		}
		$stmt->execute();
		while ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
			$earliestValid = $this->dateTimeFromUtc( $row['earliest_valid'] );
			$lastValid = $this->dateTimeFromUtc( $row['last_valid'] );

			\assert( null !== $earliestValid );
			\assert( null !== $lastValid );

			yield "{$row['requester']}@{$row['realm']}" => new RequesterAggregate(
				requester: new Requester(
					name: $row['requester'],
					realm: $row['realm'],
				),
				earliestValid: $earliestValid,
				lastValid: $lastValid,
				validOn: $validOn,
				totalAccounts: $row['total_accounts'],
				validAccounts: $row['valid_accounts'],
			);
		}
	}

	public function getRealmStats( array $realms = [], ?DateTimeInterface $validOn = null ): Generator
	{
		if ( null === $validOn ) {
			$validOn = $this->now;
		}
		$pdo = $this->profileService->getPDO();
		$extraConditions = $this->getRealmConditions( $realms, static fn( $s ) => $pdo->quote( $s ) );
		$stmt = $pdo->prepare( <<<SQL
				SELECT
					realm,
					MIN(issued) earliest_valid,
					MAX(expires) last_valid,
					COUNT("serial") total_accounts,
					COUNT(CASE WHEN revoked IS NULL THEN "serial" END) valid_accounts,
					COUNT(DISTINCT requester) total_requesters
				FROM realm_signing_log
				WHERE expires > :valid_on AND issued < :valid_on
					{$extraConditions}
				GROUP BY realm
				ORDER BY issued DESC;
			SQL );
		$stmt->bindValue( 'valid_on', $this->formatUtc( $validOn ), PDO::PARAM_STR );
		$stmt->execute();
		while ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
			$earliestValid = $this->dateTimeFromUtc( $row['earliest_valid'] );
			$lastValid = $this->dateTimeFromUtc( $row['last_valid'] );

			\assert( null !== $earliestValid );
			\assert( null !== $lastValid );

			yield $row['realm'] => [
				'realm' => $row['realm'],
				'earliest_valid' => $earliestValid,
				'last_valid' => $lastValid,
				'total_accounts' => $row['total_accounts'],
				'valid_accounts' => $row['valid_accounts'],
				'total_requesters' => $row['total_requesters'],
			];
		}
	}

	public function revokeCredential( string $credentialId, ?string $requester = null ): void
	{
		$requesterCondition = null === $requester ? '' : 'AND requester = :requester';
		$revokeStatement = $this->profileService->getPDO()->prepare( <<<SQL
				UPDATE realm_signing_log
				SET revoked = :revoked
				WHERE
					ident = :ident
					{$requesterCondition}
					AND revoked IS NULL
			SQL );
		$revokeStatement->bindValue( 'revoked', $this->formatUtc( $this->now ), PDO::PARAM_STR );
		$revokeStatement->bindParam( 'ident', $credentialId, PDO::PARAM_STR );
		if ( null !== $requester ) {
			$revokeStatement->bindValue( 'requester', $requester, PDO::PARAM_STR );
		}
		$revokeStatement->execute();
	}

	public function revokeRequester( string $requester, array $realms = [], ?DateTimeInterface $validOn = null ): void
	{
		if ( null === $validOn ) {
			$validOn = $this->now;
		}
		$pdo = $this->profileService->getPDO();
		$extraConditions = $this->getRealmConditions( $realms, static fn( $s ) => $pdo->quote( $s ) );
		$revokeStatement = $pdo->prepare( <<<SQL
				UPDATE realm_signing_log
				SET revoked = :revoked
				WHERE requester = :requester
					AND expires > :valid_on AND issued < :valid_on
					AND revoked IS NULL
					{$extraConditions}
			SQL );
		$revokeStatement->bindValue( 'valid_on', $this->formatUtc( $validOn ), PDO::PARAM_STR );
		$revokeStatement->bindValue( 'revoked', $this->formatUtc( $this->now ), PDO::PARAM_STR );
		$revokeStatement->bindValue( 'requester', $requester, PDO::PARAM_STR );
		$revokeStatement->execute();
	}

	public function getCredential( string $ident, array $realms = [] ): ?Credential
	{
		$pdo = $this->profileService->getPDO();
		$extraConditions = $this->getRealmConditions( $realms, static fn( $s ) => $pdo->quote( $s ) );
		$stmt = $pdo->prepare( <<<SQL
				SELECT
					"serial", realm, ca_sub, requester, sub, issued, expires, revoked, "usage", client, user_agent, ip, "grant", ident
					, requester, realm
				FROM realm_signing_log
				WHERE ident = :ident
					{$extraConditions}
				;
			SQL );
		$stmt->bindValue( 'ident', $ident, PDO::PARAM_STR );
		$stmt->execute();
		if ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
			$expiry = $this->dateTimeFromUtc( $row['expires'] );
			$issued = $this->dateTimeFromUtc( $row['issued'] );
			$revoked = $this->dateTimeFromUtc( $row['revoked'] );

			\assert( null !== $expiry );
			\assert( null !== $issued );

			return new CertificateCredential(
				credentialId: $row['ident'],
				userId: $row['requester'],
				clientId: $row['client'],
				grantSid: $row['grant'],
				ip: $row['ip'],
				userAgent: $row['user_agent'],
				realm: $this->admin->getRealm( $row['realm'] ),
				expiry: $expiry,
				issued: $issued,
				revoked: $revoked,
			);
		}

		return null;
	}

	public function listCredentials( array $realms = [], ?string $requester = null, ?DateTimeInterface $validOn = null, bool $unrevokedOnly = false ): Generator
	{
		if ( null === $validOn ) {
			$validOn = $this->now;
		}
		$pdo = $this->profileService->getPDO();
		$extraConditions = $this->getRealmConditions( $realms, static fn( $s ) => $pdo->quote( $s ) );
		if ( null !== $requester ) {
			$extraConditions .= ' AND requester = :requester';
		}
		if ( $unrevokedOnly ) {
			$extraConditions .= ' AND revoked IS NULL';
		}
		$stmt = $pdo->prepare( <<<SQL
				SELECT
					"serial", realm, ca_sub, requester, sub, issued, expires, revoked, "usage", client, user_agent, ip, "grant", ident
					, requester, realm
				FROM realm_signing_log
				WHERE expires > :valid_on AND issued < :valid_on
					{$extraConditions}
				ORDER BY issued DESC;
			SQL );
		$stmt->bindValue( 'valid_on', $this->formatUtc( $validOn ), PDO::PARAM_STR );
		if ( null !== $requester ) {
			$stmt->bindValue( 'requester', $requester, PDO::PARAM_STR );
		}
		$stmt->execute();
		while ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
			$expiry = $this->dateTimeFromUtc( $row['expires'] );
			$issued = $this->dateTimeFromUtc( $row['issued'] );
			$revoked = $this->dateTimeFromUtc( $row['revoked'] );

			\assert( null !== $expiry );
			\assert( null !== $issued );

			yield $row['ident'] => new CertificateCredential(
				credentialId: $row['ident'],
				userId: $row['requester'],
				clientId: $row['client'],
				grantSid: $row['grant'],
				ip: $row['ip'],
				userAgent: $row['user_agent'],
				realm: $this->admin->getRealm( $row['realm'] ),
				expiry: $expiry,
				issued: $issued,
				revoked: $revoked,
			);
		}
	}

	/**
	 * @param array<Realm|string>             $realms
	 * @param callable(string):(false|string) $sqlEscape
	 */
	private function getRealmConditions( array $realms, callable $sqlEscape ): string
	{
		if ( empty( $realms ) ) {
			$realms = $this->admin->realms;
		} else {
			foreach ( $realms as $realm ) {
				if ( !$this->admin->canUseRealm( $realm ) ) {
					throw new RealmMismatchException( $realm, provider: $this->admin->provider );
				}
			}
		}
		$realms = \array_map( static fn( Realm|string $r ) => $sqlEscape( $r instanceof Realm ? $r->realmId : $r ), $realms );
		if ( \in_array( false, $realms, true ) ) {
			throw new DomainException( 'Unable to escape realm value' );
		}
		$extraConditions = ' AND realm IN (' . \implode( ', ', $realms ) . ')';

		return $extraConditions;
	}
}
