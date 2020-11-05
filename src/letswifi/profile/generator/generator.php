<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2020, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\profile\generator;
use DateTimeInterface;
interface Generator
{
	function getExpiry(): ?DateTimeInterface;
	function generate(): string;
	function getContentType(): string;
	function getFilename(): string;
}
