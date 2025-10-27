<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\credential;

use DateTimeInterface;
use JsonSerializable;

class RequesterAggregate implements JsonSerializable
{
	/** @internal */
	public function __construct(
		public readonly Requester $requester,
		public readonly DateTimeInterface $validOn,
		public readonly DateTimeInterface $earliestValid,
		public readonly DateTimeInterface $lastValid,
		public readonly int $totalAccounts,
		public readonly int $validAccounts,
	) {
	}

	public function jsonSerialize(): array
	{
		return [
			'requester' => $this->requester,
			'valid_on' => $this->validOn,
			'earliest_valid' => $this->earliestValid,
			'last_valid' => $this->lastValid,
			'total_accounts' => $this->totalAccounts,
			'valid_accounts' => $this->validAccounts,
		];
	}
}
