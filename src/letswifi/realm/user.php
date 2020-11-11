<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2020, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
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
