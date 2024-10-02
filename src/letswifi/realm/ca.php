<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\realm;

use fyrkat\openssl\PrivateKey;
use fyrkat\openssl\X509;

class CA
{
	/** @var RealmManager */
	private $manager;

	/** @var string */
	private $pub;

	/** @var ?string */
	private $key;

	public function __construct( RealmManager $manager, string $pub, ?string $key )
	{
		$this->manager = $manager;
		$this->pub = $pub;
		$this->key = $key;
	}

	public function getX509(): X509
	{
		return new X509( $this->pub );
	}

	public function getPrivateKey(): PrivateKey
	{
		return new PrivateKey( $this->key );
	}

	public function getSubject(): string
	{
		return $this->getX509()->getSubject()->__toString();
	}

	public function getIssuerCA(): ?self
	{
		$issuerSub = $this->getX509()->getIssuerSubject();
		$sub = $this->getSubject();
		if ( $issuerSub->__toString() === $sub ) {
			return null;
		}

		return $this->manager->getCA( $issuerSub->__toString() );
	}
}
