<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\profile\auth;

use fyrkat\openssl\X509;

abstract class AbstractAuth implements Auth
{
	/** @var array<X509> */
	private $caCertificates;

	/** @var array<string> */
	private $serverNames;

	/**
	 * @param array<X509>   $caCertificates Trusted CA certificates
	 * @param array<string> $serverNames    Accepted server names
	 */
	public function __construct( array $caCertificates, array $serverNames )
	{
		$this->caCertificates = $caCertificates;
		$this->serverNames = $serverNames;
	}

	/**
	 * @return array<X509>
	 */
	public function getServerCACertificates(): array
	{
		return $this->caCertificates;
	}

	/**
	 * @return array<string>
	 */
	public function getServerNames(): array
	{
		return $this->serverNames;
	}
}
