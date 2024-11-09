<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\credential;

use Closure;
use DateTimeInterface;
use fyrkat\openssl\PKCS12;
use letswifi\provider\Provider;
use letswifi\provider\Realm;
use letswifi\provider\User;

class CertificateCredential extends Credential
{
	private ?PKCS12 $pkcs12 = null;

	private ?PKCS12 $pkcs12des = null;

	private ?PKCS12 $pkcs12noCA = null;

	private ?PKCS12 $pkcs12noCAdes = null;

	/**
	 * @param Closure():?PKCS12 $pkcs12Generator
	 */
	public function __construct(
		User $user,
		Realm $realm,
		Provider $provider,
		private readonly Closure $pkcs12Generator,
	) {
		parent::__construct( $user, $realm, $provider );
	}

	public function getPKCS12( bool $ca = true, bool $des = false ): ?PKCS12
	{
		if ( null === $this->pkcs12 ) {
			$this->pkcs12 = $this->generateClientCertificate();
		}
		if ( null === $this->pkcs12 ) {
			return null;
		}

		switch ( [$ca, $des] ) {
			case [false, false]:return $this->pkcs12noCA ?? $this->pkcs12noCA = new PKCS12( $this->pkcs12->x509, $this->pkcs12->privateKey );
			case [true, false]:return $this->pkcs12;
			case [false, true]:return $this->pkcs12noCAdes ?? $this->getPKCS12( false, false )?->use3des();
			case [true, true]:return $this->pkcs12des ?? $this->pkcs12des = $this->pkcs12->use3des();
		}

		return null;
	}

	public function getExpiry(): ?DateTimeInterface
	{
		return null === $this->pkcs12 ? null : $this->pkcs12->x509->getValidTo();
	}

	public function getIdentity(): ?string
	{
		$pkcs12 = $this->getPKCS12();

		return null === $pkcs12
			? null
			: $pkcs12->x509->getSubject()->getCommonName();
	}

	private function generateClientCertificate(): ?PKCS12
	{
		$f = $this->pkcs12Generator;

		return $f();
	}
}
