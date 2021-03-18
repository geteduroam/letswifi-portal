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
	private $userID;

	public function __construct( string $userID )
	{
		$this->userID = $userID;
	}

	public function getUserID(): string
	{
		return $this->userID;
	}
}
