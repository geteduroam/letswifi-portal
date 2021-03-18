<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2021, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * Copyright: 2020-2021, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\profile\auth;

use DateTimeInterface;

use fyrkat\openssl\PKCS12;
use fyrkat\openssl\X509;

class TlsAuth extends AbstractAuth
{
	/** @var ?PKCS12 */
	private $pkcs12;

	/** @var string */
	private $passphrase;

	/**
	 * @param array<X509>   $caCertificates Trusted CA certificates
	 * @param array<string> $serverNames    Accepted server names
	 * @param ?PKCS12       $pkcs12         Certificate for user/device authentication
	 * @param ?string       $passphrase     Transient password to be used for encrypting the PKCS12 payload
	 */
	public function __construct( array $caCertificates, array $serverNames, ?PKCS12 $pkcs12, ?string $passphrase = null )
	{
		parent::__construct( $caCertificates, $serverNames );
		$this->pkcs12 = $pkcs12;
		$this->passphrase = $passphrase ?? 'pkcs12';
	}

	public function getExpiry(): ?DateTimeInterface
	{
		return null === $this->pkcs12 ? null : $this->pkcs12->getX509()->getValidTo();
	}

	public function getPKCS12(): ?PKCS12
	{
		return $this->pkcs12;
	}

	public function getPassphrase(): string
	{
		return $this->passphrase;
	}

	public function getIdentity(): ?string
	{
		$pkcs12 = $this->getPKCS12();

		return null === $pkcs12
			? null
			: $pkcs12->getX509()->getSubject()->getCommonName()
			;
	}
}
