<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\provider;

use letswifi\Config;

class Contact
{
	public function __construct(
		public readonly ?string $mail = null,
		public readonly ?string $web = null,
		public readonly ?string $phone = null,
		public readonly ?Location $location = null,
		public readonly ?Logo $logo = null,
	) {
	}

	public static function fromConfig( Config $contactData ): self
	{
		$location = $contactData->getDictionary( 'location' );
		$logo = $contactData->getDictionary( 'logo' );

		return new self(
			mail: $contactData->getStringOrNull( 'mail' ),
			web: $contactData->getStringOrNull( 'web' ),
			phone: $contactData->getStringOrNull( 'phone' ),
			location: Location::fromConfig( $contactData->getDictionary( 'location' ) ),
			logo: Logo::fromConfig( $contactData->getDictionary( 'logo' ) ),
		);
	}
}
