<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2021, Jørn Åne de Jong, Uninett AS <jornane.dejong@surf.nl>
 * Copyright: 2020-2021, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\profile;

class Helpdesk
{
	/** @var ?string */
	private $mail;

	/** @var ?string */
	private $web;

	/** @var ?string */
	private $phone;

	public function __construct( ?string $mail, ?string $web, ?string $phone )
	{
		$this->mail = $mail;
		$this->web = $web;
		$this->phone = $phone;
	}

	public function getMail(): ?string
	{
		return $this->mail;
	}

	public function getWeb(): ?string
	{
		return $this->web;
	}

	public function getPhone(): ?string
	{
		return $this->phone;
	}
}
