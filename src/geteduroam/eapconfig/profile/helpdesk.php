<?php declare(strict_types=1);

/*
 * This file is part of geteduroam; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2020, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace geteduroam\EapConfig\Profile;

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

	public function generateEapConfigXml(): string
	{
		$result = "\r\n\t\t\t<Helpdesk>";
		if ( null !== $this->mail ) {
			$result .= "\r\n\t\t\t\t<EmailAddress>" . static::e( $this->mail ) . '</EmailAddress>';
		}
		if ( null !== $this->web ) {
			$result .= "\r\n\t\t\t\t<WebAddress>" . static::e( $this->web ) . '</WebAddress>';
		}
		if ( null !== $this->phone ) {
			$result .= "\r\n\t\t\t\t<Phone>" . static::e( $this->phone ) . '</Phone>';
		}
		$result .= "\r\n\t\t\t</Helpdesk>";

		return $result;
	}

	private static function e( string $s ): string
	{
		return \htmlspecialchars( $s, \ENT_QUOTES, 'UTF-8' );
	}
}
