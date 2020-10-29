<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2020, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\EapConfig\Auth;

use DateTimeInterface;

interface IAuthenticationMethod
{
	public function generateEapConfigXml(): string;

	public function getExpiry(): ?DateTimeInterface;
}
