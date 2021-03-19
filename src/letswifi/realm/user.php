<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2021, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * Copyright: 2020-2021, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\realm;

class User
{
	/** @var string */
	private $userId;

	/** @var ?string */
	private $clientId;

	/** @var ?string */
	private $ip;

	/** @var ?string */
	private $userAgent;

	public function __construct( string $userId, ?string $clientId = null, ?string $ip = null, ?string $userAgent = null )
	{
		$this->userId = $userId;
		$this->clientId = $clientId;
		$this->ip = $ip;
		$this->userAgent = $userAgent;
	}

	public function getUserID(): string
	{
		return $this->userId;
	}

	public function getClientId(): ?string
	{
		return $this->clientId;
	}

	public function getIP(): ?string
	{
		return $this->ip;
	}

	public function getUserAgent(): ?string
	{
		return $this->userAgent;
	}
}
