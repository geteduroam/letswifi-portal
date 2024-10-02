<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\browserauth;

use RuntimeException;

class MismatchRealmSelectorException extends RuntimeException
{
	/** @var array */
	private $realmSelectors;

	/** @var ?string */
	private $attribute;

	/** @var ?string $ */
	private $username;

	public function __construct( array $realmSelectors, ?string $attribute )
	{
		$this->realmSelectors = $realmSelectors;
		$this->attribute = $attribute;
		parent::__construct(
			'Realm selectors " ' . \implode( '", "', $realmSelectors ) . ' " ' .
				(
					null !== $attribute
						? "(from attribute \"{$attribute}\") "
						: ''
				) . 'are unknown',
		);
	}
}
