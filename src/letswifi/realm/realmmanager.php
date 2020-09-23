<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2020, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\realm;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DomainException;

use fyrkat\openssl\CSR;
use fyrkat\openssl\PrivateKey;
use fyrkat\openssl\X509;

use InvalidArgumentException;
use PDO;

class RealmManager extends DatabaseStorage
{
	public function __construct( PDO $pdo )
	{
		parent::__construct( $pdo );
	}

	public function getRealm( string $realm ): Realm
	{
		// Guarantee that realm exists
		if ( null === $this->getSingleFieldFromTableWhere( 'realm', 'realm', ['realm' => $realm] ) ) {
			throw new InvalidArgumentException( "Realm ${realm} does not exist" );
		}

		return new Realm( $this, $realm );
	}

	/**
	 * @internal
	 *
	 * @return array<string>
	 */
	public function getServerNames( string $realm ): array
	{
		return $this->getFieldsFromTableWhere( 'realm_server_name', 'server_name', ['realm' => $realm] );
	}

	/**
	 * @internal
	 *
	 * @return array<CA>
	 */
	public function getTrustedCas( string $realm ): array
	{
		$result = [];
		foreach ( $this->getFieldsFromTableWhere( 'realm_trust', 'trusted_ca_sub', ['realm' => $realm] ) as $sub ) {
			$ca = $this->getCA( $sub );
			// TODO throw exception?
			if ( null !== $ca ) {
				$result[] = $ca;
			}
		}

		return $result;
	}

	/**
	 * @internal
	 *
	 * @return CA
	 */
	public function getSignerCa( string $realm ): CA
	{
		$sub = $this->getSingleFieldFromTableWhere( 'realm_signer', 'signer_ca_sub', ['realm' => $realm] );
		$ca = $this->getCA( $sub );
		if ( null === $ca ) {
			throw new DomainException( 'Signer CA not found' );
		}

		return $ca;
	}

	/**
	 * @internal
	 *
	 * @return DateInterval
	 */
	public function getDefaultValidity( string $realm ): DateInterval
	{
		$validity = $this->getSingleFieldFromTableWhere( 'realm_signer', 'default_validity_days', ['realm' => $realm] );

		return new DateInterval( "P${validity}D" );
	}

	/**
	 * Get realm name for HTTP hostname
	 *
	 * @param string $httpHost Hostname, aka $_SERVER['HTTP_HOST']
	 *
	 * @return ?string Realm name to be used for RealmManager::getRealm()
	 */
	public function getRealmNameByHttpHost( string $httpHost ): ?string
	{
		return $this->getSingleFieldFromTableWhere( 'realm_vhost', 'realm', ['http_host' => $httpHost] );
	}

	/**
	 * @internal
	 *
	 * @return string
	 */
	public function getCurrentOAuthKey( string $realm, ?int $now = null ): string
	{
		$entries = $this->getValidOAuthKeys( $realm, $now );
		\usort(
				$entries,
				static function( array $a, array $b ): int {
					return (int)( $a['issued'] - $b['issued'] );
				}
			);

		$key = \end( $entries )['key'];
		if ( null === $key ) {
			throw new DomainException( "Missing OAuth key for realm ${realm}" );
		}
		$result = \base64_decode( $key, true );
		if ( false === $result ) {
			throw new DomainException( 'OAuth key is not valid base64 data' );
		}

		return $result;
	}

	/**
	 * @suppress PhanUnextractableAnnotationSuffix Phan doesn't understand array<array-key,array<array-key,mixed>>
	 *
	 * @internal
	 *
	 * @return array<array-key,array<array-key,mixed>>
	 */
	public function getValidOAuthKeys( string $realm, ?int $now = null ): array
	{
		if ( null === $now ) {
			$now = \time();
		}

		return $this->getEntriesFromTableWhere( 'realm_key', ['realm' => $realm, 'issued' => $now, 'expires' => $now]);
	}

	/**
	 * @internal
	 */
	public function getCA( string $sub ): ?CA
	{
		$entry = $this->getSingleEntryFromTableWhere( 'ca', ['sub' => $sub] );
		if ( null === $entry ) {
			return null;
		}

		return new CA( $this, $entry['pub'], $entry['key'] );
	}

	/**
	 * @internal
	 * @suppress PhanPossiblyNonClassMethodCall
	 * @suppress PhanPluginUseReturnValueInternalKnown
	 */
	public function logPreparedCredential( string $realm, X509 $caCert, User $requester, CSR $csr, DateTimeInterface $expiry, string $usage ): int
	{
		$csrData = $csr->getCSRPem();
		$statement = $this->pdo->prepare( 'INSERT INTO `realm_signing_log` (`realm`, `ca_sub`, `requester`, `usage`, `sub`, `issued`, `expires`, `csr`) VALUES (:realm, :ca_sub, :requester, :usage, :sub, :issued, :expires, :csr)' );
		$statement->bindValue( 'realm', $realm, PDO::PARAM_STR );
		$statement->bindValue( 'ca_sub', $caCert->getSubject(), PDO::PARAM_STR );
		$statement->bindValue( 'requester', $requester->getUserID(), PDO::PARAM_STR );
		$statement->bindValue( 'usage', $usage, PDO::PARAM_STR );
		$statement->bindValue( 'sub', $csr->getSubject(), PDO::PARAM_STR );
		$statement->bindValue( 'issued', \date( 'Y-m-d' ), PDO::PARAM_STR );
		$statement->bindValue( 'expires', $expiry->format( 'Y-m-d' ), PDO::PARAM_STR );
		$statement->bindValue( 'csr', $csrData, PDO::PARAM_STR );
		$statement->execute();
		$last = $this->pdo->lastInsertId();
		$lastId = (int)$last;
		if ( $lastId > 0 && (string)$lastId === $last ) {
			return $lastId;
		}
		throw new DomainException( 'Unable to retrieve last insert ID from database' );
	}

	/**
	 * @internal
	 * @suppress PhanPossiblyNonClassMethodCall Phan doesn't understand PDO
	 */
	public function logCompletedCredential( string $realm, User $user, X509 $userCert, string $usage ): void
	{
		$statement = $this->pdo->prepare( 'UPDATE `realm_signing_log` SET `issued` = :issued, `expires` = :expires, `x509` = :x509 WHERE `serial` = :serial AND `realm` = :realm AND `requester` = :requester AND `usage` = :usage AND `ca_sub` = :ca_sub' );
		$statement->bindValue( 'issued', $userCert->getValidFrom()->format( 'Y-m-d' ), PDO::PARAM_STR );
		$statement->bindValue( 'expires', $userCert->getValidTo()->format( 'Y-m-d' ), PDO::PARAM_STR );
		$statement->bindValue( 'x509', $userCert->getX509Pem(), PDO::PARAM_STR );
		$statement->bindValue( 'serial', $userCert->getSerialNumber(), PDO::PARAM_INT );
		$statement->bindValue( 'realm', $realm, PDO::PARAM_STR );
		$statement->bindValue( 'requester', $user->getUserID(), PDO::PARAM_STR );
		$statement->bindValue( 'usage', $usage, PDO::PARAM_STR );
		$statement->bindValue( 'ca_sub', $userCert->getIssuerSubject(), PDO::PARAM_STR );
		$statement->execute();
		$rows = $statement->rowCount();
		if ( 1 !== $rows ) {
			throw new DomainException( "Unable to log signed certificate; expected 1 rows to be updated but got ${rows}" );
		}
	}

	/**
	 * @suppress PhanPossiblyNonClassMethodCall Phan doesn't understand PDO
	 */
	public function createRealm( string $realm ): void
	{
		$statement = $this->pdo->prepare( 'INSERT INTO `realm` (`realm`) VALUES (:realm)' );
		$statement->bindValue( 'realm', $realm );
		$statement->execute();

		// Not really "rotate", but creating the initial key is the same procedure so..
		$this->rotateOAuthKey( $realm );
	}

	/**
	 * @suppress PhanPossiblyNonClassMethodCall Phan doesn't understand PDO
	 */
	public function rotateOAuthKey( string $realm, int $grace = 3600, int $now = null ): void
	{
		if ( null === $now ) {
			$now = \time();
		}
		$this->pdo->beginTransaction();

		$statement1 = $this->pdo->prepare( 'UPDATE `realm_key` SET `expires` = :expires WHERE `realm` = :realm AND `expires` IS NULL' );
		$statement1->bindValue( 'realm', $realm );
		$statement1->bindValue( 'expires', $now + $grace );

		$statement2 = $this->pdo->prepare( 'INSERT INTO `realm_key` (`realm`, `key`, `issued`) VALUES (:realm, :key, :issued)');
		$statement2->bindValue( 'realm', $realm );
		$statement2->bindValue( 'key', \base64_encode( \random_bytes( 32 ) ) );
		$statement2->bindValue( 'issued', $now );

		$statement1->execute();
		$statement2->execute();

		$this->pdo->commit();
	}

	/**
	 * @suppress PhanPossiblyNonClassMethodCall Phan doesn't understand PDO
	 */
	public function importCA( X509 $x509, ?PrivateKey $privateKey ): void
	{
		$issuer = $x509->getIssuerSubject()->__toString();
		$sub = $x509->getSubject()->__toString();

		if ( $issuer !== $sub ) {
			if ( null === $this->getCA( $sub ) ) {
				throw new InvalidArgumentException( 'Attempted to import intermediate certificate without known root' );
			}
		}

		$statement = $this->pdo->prepare( 'INSERT INTO `ca` (`sub`, `pub`, `key`, `issuer`) VALUES (:sub, :pub, :key, :issuer)' );
		$statement->bindValue( 'sub', $sub );
		$statement->bindValue( 'pub', $x509->getX509Pem() );
		$statement->bindValue( 'key', $privateKey ? $privateKey->getPrivateKeyPem( null ) : null );
		$statement->bindValue( 'issuer', $issuer === $sub ? null : $issuer );
		$statement->execute();
	}

	/**
	 * @suppress PhanPossiblyNonClassMethodCall Phan doesn't understand PDO
	 */
	public function addTrustedCa( string $realm, string $sub ): void
	{
		if ( null === $this->getCA( $sub ) ) {
			throw new InvalidArgumentException( "Attempted to trust CA ${sub}, but it is not known" );
		}

		$statement = $this->pdo->prepare( 'INSERT INTO `realm_trust` (`realm`, `trusted_ca_sub`) VALUES (:realm, :sub)' );
		$statement->bindValue( 'realm', $realm );
		$statement->bindValue( 'sub', $sub );
		$statement->execute();
	}

	/**
	 * @suppress PhanPossiblyNonClassMethodCall Phan doesn't understand PDO
	 */
	public function removeTrustedCa( string $realm, string $sub ): void
	{
		$statement = $this->pdo->prepare( 'DELETE FROM `realm_trust` WHERE `realm` = :realm AND trusted_ca_sub = :sub' );
		$statement->bindValue( 'realm', $realm );
		$statement->bindValue( 'sub', $sub );
		$statement->execute();
	}

	/**
	 * @suppress PhanPossiblyNonClassMethodCall Phan doesn't understand PDO
	 */
	public function setSignerCa( string $realm, string $sub, DateInterval $defaultValidity ): void
	{
		if ( null === $this->getCA( $sub ) ) {
			throw new InvalidArgumentException( "Attempted to trust CA ${sub}, but it is not known" );
		}

		$epoch = new DateTimeImmutable( '@0' );
		$validitySeconds = $epoch->add( $defaultValidity )->getTimestamp();

		$statement = $this->pdo->prepare( 'REPLACE INTO `realm_signer` (`realm`, `signer_ca_sub`, `default_validity_days`) VALUES (:realm, :sub, :validity_seconds)' );
		$statement->bindValue( 'realm', $realm );
		$statement->bindValue( 'sub', $sub );
		$statement->bindValue( 'validity_seconds', $validitySeconds );
		$statement->execute();
	}

	/**
	 * @suppress PhanPossiblyNonClassMethodCall Phan doesn't understand PDO
	 */
	public function addServer( string $realm, string $serverName ): void
	{
		$statement = $this->pdo->prepare( 'INSERT INTO `realm_server_name` (`realm`, `server_name`) VALUES (:realm, :server_name)' );
		$statement->bindValue( 'realm', $realm );
		$statement->bindValue( 'server_name', $serverName );
		$statement->execute();
	}

	/**
	 * @suppress PhanPossiblyNonClassMethodCall Phan doesn't understand PDO
	 */
	public function removeServer( string $realm, string $serverName ): void
	{
		$statement = $this->pdo->prepare( 'DELETE FROM `realm_server_name` WHERE `realm` = :realm AND `server_name` = :server_name' );
		$statement->bindValue( 'realm', $realm );
		$statement->bindValue( 'server_name', $serverName );
		$statement->execute();
	}

	/**
	 * @suppress PhanPossiblyNonClassMethodCall Phan doesn't understand PDO
	 */
	public function addVhost( string $realm, string $httpHost ): void
	{
		$statement = $this->pdo->prepare( 'INSERT INTO `realm_vhost` (`realm`, `server_name`) VALUES (:realm, :server_name)' );
		$statement->bindValue( 'realm', $realm );
		$statement->bindValue( 'http_host', $httpHost );
		$statement->execute();
	}

	/**
	 * @suppress PhanPossiblyNonClassMethodCall Phan doesn't understand PDO
	 */
	public function removeVhost( string $realm, string $httpHost ): void
	{
		$statement = $this->pdo->prepare( 'DELETE FROM `realm_vhost` WHERE `realm` = :realm AND `http_host` = :httpHost' );
		$statement->bindValue( 'realm', $realm );
		$statement->bindValue( 'http_host', $httpHost );
		$statement->execute();
	}
}
