<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\format;

use letswifi\credential\CertificateCredential;

class Pkcs12Format extends Format
{
	/**
	 * Generate the eap-config profile
	 */
	public function generate(): string
	{
		\assert( $this->credential instanceof CertificateCredential ); // We don't support anything else yet
		$pkcs12 = $this->credential->getPKCS12( ca: true, des: true );

		return $pkcs12->getPKCS12Bytes( $this->passphrase ?: '' );
	}

	public function getFileExtension(): string
	{
		return 'p12';
	}

	public function getContentType(): string
	{
		return 'application/x-pkcs12';
	}
}
