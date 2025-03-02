<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\tenant;

use JsonSerializable;
use letswifi\configuration\Dictionary;

class Contact implements JsonSerializable
{
	/**
	 * @param array<Location> $location
	 */
	public function __construct(
		public readonly ?string $mail = null,
		public readonly ?string $web = null,
		public readonly ?string $phone = null,
		public readonly array $location = [],
		public readonly ?Logo $logo = null,
	) {
	}

	/**
	 * @return array{mail:?string,web:?string,phone:?string,location:array<Location>,logo:bool}
	 */
	public function jsonSerialize(): array
	{
		return [
			'mail' => $this->mail,
			'web' => $this->web,
			'phone' => $this->phone,
			'location' => $this->location,
			'logo' => isset( $this->logo ),
		];
	}

	public static function fromConfig( Dictionary $contactData ): self
	{
		$location = $contactData->getDictionaryList( 'location' );
		$logo = $contactData->getDictionaryOrNull( 'logo' );

		return new self(
			mail: $contactData->getStringOrNull( 'mail' ),
			web: $contactData->getStringOrNull( 'web' ),
			phone: $contactData->getStringOrNull( 'phone' ),
			location: \array_map( [Location::class, 'fromConfig'], $location ),
			logo: null === $logo ? null : Logo::fromConfig( $logo ),
		);
	}
}
