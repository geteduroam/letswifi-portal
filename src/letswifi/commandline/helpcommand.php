<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\commandline;

class HelpCommand extends Command
{
	public function run(): void
	{
		$bold = static::BOLD;
		$normal = static::NORMAL;
		$output = [
			"usage:  {$bold}{$this->argv[0]}{$normal} command args ...",
			'where command is one of the following:',
			'',
		];
		foreach ( static::COMMANDS as $command => $class ) {
			foreach ( $class::HELP as $help ) {
				$output[] = "\t" . static::BOLD . $command . static::NORMAL . \rtrim( " {$help}" );
			}
		}
		static::print_error( ...$output );

		exit( 1 );
	}
}
