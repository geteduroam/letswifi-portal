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
use Generator;
use PDO;
use letswifi\error\RealmMismatchException;
use letswifi\profile\Realm;

/**
 * @internal
 */
class CertificateCredentialAdmin extends CredentialAdmin
{
	use Database;
	use GMT;

	public const DATE_FORMAT = 'Y-m-d H:i:s';

	public function listRequesters( array $realms = [], ?DateTimeInterface $validOn = null, ?string $requester = null ): Generator
	{
		if ( null === $validOn ) {
			$validOn = $this->now;
		}
		$pdo = $this->getPDO();
		$extraConditions = '';
		if ( empty( $realms ) ) {
			$realms = $this->admin->realms;
		} else {
			foreach ( $realms as $realm ) {
				if ( !$this->admin->canUseRealm( $realm ) ) {
					throw new RealmMismatchException( $realm, provider: $this->admin->provider );
				}
			}
		}
		$realms = \array_map( static fn( Realm|string $r ) => $pdo->quote( $r instanceof Realm ? $r->realmId : $r ), $realms );
		$extraConditions = ' AND realm IN (' . \implode( ', ', $realms ) . ')';
		if ( null !== $requester ) {
			$extraConditions .= ' AND requester = :requester';
		}
		$stmt = $this->getPDO()->prepare( <<<SQL
				SELECT
					requester, realm,
					MIN(issued) earliest_valid,
					MAX(expires) last_valid,
					COUNT("serial") total_accounts,
					COUNT(CASE WHEN revoked IS NULL THEN "serial" END) valid_accounts
				FROM realm_signing_log
				WHERE expires > :valid_on AND issued < :valid_on
					{$extraConditions}
				GROUP BY requester, realm;
			SQL );
		$stmt->bindValue( 'valid_on', \gmdate( static::DATE_FORMAT, $validOn->getTimestamp() ), PDO::PARAM_STR );
		if ( null !== $requester ) {
			$stmt->bindValue( 'requester', $requester, PDO::PARAM_STR );
		}
		$stmt->execute();
		while ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
			$earliestValid = $this->dateTimeFromGmt( $row['earliest_valid'] );
			$lastValid = $this->dateTimeFromGmt( $row['last_valid'] );
			\assert( null !== $earliestValid );
			\assert( null !== $lastValid );

			yield "{$row['requester']}@{$row['realm']}" => new RequesterAggregate(
				requester: new Requester(
					name: $row['requester'],
					realm: $row['realm'],
					revoke: fn() => $this->revokeRequester( $row['requester'] ),
				),
				earliestValid: $earliestValid,
				lastValid: $lastValid,
				validOn: $validOn,
				totalAccounts: $row['total_accounts'],
				validAccounts: $row['valid_accounts'],
				revoke: fn() => $this->revokeRequester( $row['requester'], $validOn ),
			);
		}
	}

	public function revokeCredential( string $credentialId, ?string $requester = null ): void
	{
		$requesterCondition = null === $requester ? '' : 'AND requester = :requester';
		$revokeStatement = $this->getPDO()->prepare( <<<SQL
			UPDATE realm_signing_log
			SET revoked = :revoked
			WHERE
				ident = :ident
				{$requesterCondition}
				AND revoked IS NULL
			SQL );
		$revokeStatement->bindValue( 'revoked', \gmdate( static::DATE_FORMAT, $this->now->getTimestamp() ), PDO::PARAM_STR );
		$revokeStatement->bindParam( 'ident', $credentialId, PDO::PARAM_STR );
		if ( null === $requester ) {
			$revokeStatement->bindValue( 'requester', $requester, PDO::PARAM_STR );
		}
		$revokeStatement->execute();
	}

	public function revokeRequester( ?string $requester, ?DateTimeInterface $validOn = null, string|Realm|null $realm = null ): void
	{
		if ( null === $validOn ) {
			$validOn = $this->now;
		}
		if ( $realm instanceof Realm ) {
			$realm = $realm->realmId;
		}
		$extraConditions = '';
		if ( null !== $realm ) {
			$extraConditions = ' AND realm = :realm';
		}
		$revokeStatement = $this->getPDO()->prepare( <<<SQL
				UPDATE realm_signing_log
				SET revoked = :revoked
				WHERE requester = :requester
					AND expires > :valid_on AND issued < :valid_on
					AND revoked IS NULL
					{$extraConditions}
			SQL );
		$revokeStatement->bindValue( 'valid_on', \gmdate( static::DATE_FORMAT, $validOn->getTimestamp() ), PDO::PARAM_STR );
		$revokeStatement->bindValue( 'revoked', \gmdate( static::DATE_FORMAT, $this->now->getTimestamp() ), PDO::PARAM_STR );
		$revokeStatement->bindValue( 'requester', $requester, PDO::PARAM_STR );
		if ( null !== $realm ) {
			$revokeStatement->bindValue( 'realm', $realm, PDO::PARAM_STR );
		}
		$revokeStatement->execute();
	}
}
