<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\provider;

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

	public static function fromArray( array $contactData ): self
	{
		return new self(
			mail: $contactData['mail'],
			web: $contactData['web'],
			phone: $contactData['phone'],
			location: null === $contactData['location'] ? null : Location::fromArray( $contactData['location'] ),
			logo: null === $contactData['logo'] ? null : Logo::fromArray( $contactData['logo'] ),
		);
	}
}
