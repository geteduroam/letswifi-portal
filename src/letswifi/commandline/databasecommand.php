<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\commandline;

class DatabaseCommand extends Command
{
	public const HELP = [
		// self::BOLD . 'init' . self::NORMAL . '|' . self::BOLD . 'upgrade' . self::NORMAL
	];

	public function run(): void
	{
	}
}
