<?php declare(strict_types=1);

/*
 * This file is part of geteduroam; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2020, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace geteduroam\EapConfig\Auth;

use fyrkat\openssl\X509;

abstract class AbstractAuthenticationMethod implements IAuthenticationMethod
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
