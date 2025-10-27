<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\credential;

use Closure;
use DateTimeInterface;
use DomainException;
use fyrkat\openssl\PKCS12;
use letswifi\profile\Realm;

/**
 * @extends Credential<PKCS12>
 */
class CertificateCredential extends Credential
{
	private readonly DateTimeInterface $expiry;

	private readonly DateTimeInterface $issued;

	/**
	 * @param Closure():void $revoke
	 */
	public function __construct(
		?string $credentialId,
		string $userId,
		?string $clientId,
		?string $grantSid,
		?string $ip,
		?string $userAgent,
		Realm $realm,
		?DateTimeInterface $expiry = null,
		?DateTimeInterface $issued = null,
		private readonly ?DateTimeInterface $revoked = null,
		?string $anonymousIdentity = null,
		private readonly ?PKCS12 $pkcs12 = null,
	) {
		parent::__construct(
			credentialId: $credentialId,
			userId: $userId,
			clientId: $clientId,
			grantSid: $grantSid,
			ip: $ip,
			userAgent: $userAgent,
			realm: $realm,
		);

		if ( null === $pkcs12 ) {
			$this->expiry = $expiry ?? throw new DomainException( 'Expiry not provided' );
			$this->issued = $issued ?? throw new DomainException( 'Issued not provided' );
		} else {
			$this->expiry = $pkcs12->x509->getValidTo();
			$this->issued = $pkcs12->x509->getValidFrom();
		}
	}

	public function getPayload(): PKCS12
	{
		return $this->getPKCS12();
	}

	public function getPKCS12( bool $ca = true, bool $des = false ): PKCS12
	{
		$pkcs12 = $this->pkcs12
				?? throw new DomainException( 'Cannot create PKCS12 because private key is not available' );

		switch ( [$ca, $des] ) {
			case [false, false]:return new PKCS12( $pkcs12->x509, $pkcs12->privateKey );
			case [false, true]:return $this->getPKCS12( false )->use3des();
			case [true, true]:return $this->getPKCS12( true )->use3des();
			case [true, false]:
			default:
				return $pkcs12;
		}
	}

	public function getExpiry(): DateTimeInterface
	{
		return $this->expiry;
	}

	public function getIssued(): DateTimeInterface
	{
		return $this->issued;
	}

	public function getRevoked(): ?DateTimeInterface
	{
		return $this->revoked;
	}

	public function isRevoked(): bool
	{
		return null !== $this->revoked;
	}

	public function getAnonymousIdentity(): ?string
	{
		return null;
	}
}
