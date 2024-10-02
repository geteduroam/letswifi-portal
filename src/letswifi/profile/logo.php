<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\profile;

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
