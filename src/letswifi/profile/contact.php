<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\profile;

use JsonSerializable;
use letswifi\configuration\Dictionary;

class Contact implements JsonSerializable
{
	public function __construct(
		public readonly ?string $mail = null,
		public readonly ?string $web = null,
		public readonly ?string $phone = null,
	) {
	}

	/**
	 * @return array{mail:?string,web:?string,phone:?string}
	 */
	public function jsonSerialize(): array
	{
		return [
			'mail' => $this->mail,
			'web' => $this->web,
			'phone' => $this->phone,
		];
	}

	public static function fromConfig( Dictionary $contactData ): self
	{
		return new self(
			mail: $contactData->getStringOrNull( 'mail' ),
			web: $contactData->getStringOrNull( 'web' ),
			phone: $contactData->getStringOrNull( 'phone' ),
		);
	}
}
