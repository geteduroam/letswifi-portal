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
		if ( isset( $this->argv[1] ) ) {
			if ( \array_key_exists( $this->argv[1], self::COMMANDS ) ) {
				$class = self::COMMANDS[$this->argv[1]];
				$command = new $class( ['--help'] );
			} else {
				static::print_error( "Unknown command: {$this->argv[1]}" );
			}
			if ( isset( $command ) ) {
				$this->commandHelp( $this->argv[1], $command );

				return;
			}
		}

		$this->globalHelp();
	}

	public function commandHelp( string $name, Command $command ): void
	{
		$bold = static::BOLD;
		$normal = static::NORMAL;
		if ( \count( $command::HELP ) === 1 ) {
			$output = [
				"usage:  {$bold}{$this->argv[0]} {$name}{$normal} " . \current( $command::HELP ),
			]; if ( \count( $command::LONG_HELP ) === 1 ) {
				$output[] = "\n\t" . \str_replace(
					"\n",
					"\n\t",
					(string)\current( $command::LONG_HELP ),
				) . "\n";
			}
		} else {
			$output = [
				"usage:  {$bold}{$this->argv[0]} {$name}{$normal} args ...",
				'',
			];
			foreach ( $command::HELP as $i => $_ ) {
				$output[] = "\t" . \rtrim( " {$command::HELP[$i]}" );
				if ( \array_key_exists( $i, $command::LONG_HELP ) ) {
					$output[] = "\t\t" . \str_replace(
						"\n",
						"\n\t\t",
						(string)$command::LONG_HELP[$i],
					) . "\n";
				}
			}
		}
		echo \implode( \PHP_EOL, $output ) . \PHP_EOL;

		exit( 1 );
	}

	public function globalHelp(): void
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
		echo \implode( \PHP_EOL, $output ) . \PHP_EOL;

		exit( 1 );
	}
}
