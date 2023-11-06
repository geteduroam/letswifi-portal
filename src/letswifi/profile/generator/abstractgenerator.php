<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2022, Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: 2020-2022, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\profile\generator;

use DateTimeInterface;
use letswifi\profile\auth\Auth;
use letswifi\profile\IProfileData;

abstract class AbstractGenerator implements Generator
{
	/**
	 * Data about the institution
	 *
	 * @var IProfileData
	 */
	protected $profileData;

	/**
	 * Possible authentication methods
	 *
	 * @var array<Auth>
	 */
	protected $authenticationMethods;

	/**
	 * Passphrase to encrypt the profile with
	 *
	 * @var ?string */
	protected $passphrase;

	/**
	 * Create a new generator.
	 *
	 * @param IProfileData $profileData           Profile data
	 * @param array<Auth>  $authenticationMethods Authentication methods
	 */
	public function __construct( IProfileData $profileData, array $authenticationMethods, ?string $passphrase = null )
	{
		$this->profileData = $profileData;
		$this->authenticationMethods = $authenticationMethods;
		$this->passphrase = $passphrase;
	}

	public function getFilename(): string
	{
		$identifier = \implode( '.', \array_reverse( \explode( '.', $this->profileData->getRealm() ) ) );
		$datetime = \date( 'YmdHis' );
		$extension = $this->getFileExtension();

		return "${identifier}.${datetime}.${extension}";
	}

	abstract public function getFileExtension(): string;

	abstract public function getContentType(): string;

	abstract public function generate(): string;

	public function getExpiry(): ?DateTimeInterface
	{
		$result = null;
		foreach ( $this->authenticationMethods as $authentication ) {
			$expiry = $authentication->getExpiry();
			if ( null !== $expiry ) {
				if ( null === $result || $result->getTimestamp() > $expiry->getTimestamp() ) {
					$result = $expiry;
				}
			}
		}

		return $result;
	}

	protected static function e( string $s ): string
	{
		return \htmlspecialchars( $s, \ENT_QUOTES, 'UTF-8' );
	}

	protected static function columnFormat( string $data, int $length = null, int $indentation = 0 ): string
	{
		$length = $length ?: \strlen( $data ) * 4;
		\assert( 0 < $length, 'Cannot format columns with width less than 1' );

		return \implode(
				"\n" . \str_repeat( "\t", $indentation ),
				\str_split( $data, $length ),
			);
	}

	protected static function uuidgen(): string
	{
		return ( new UUID() )->__toString();
	}
}
