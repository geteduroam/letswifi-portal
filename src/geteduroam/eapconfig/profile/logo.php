<?php declare(strict_types=1);

/*
 * This file is part of geteduroam; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2020, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace geteduroam\EapConfig\Profile;

class Logo
{
	/** @var string */
	private $bytes;

	/** @var string */
	private $contentType;

	public function __construct( string $bytes, string $contentType )
	{
		$this->bytes = $bytes;
		$this->contentType = $contentType;
	}

	public function getBytes(): string
	{
		return $this->bytes;
	}

	public function getContentType(): string
	{
		return $this->contentType;
	}
}
