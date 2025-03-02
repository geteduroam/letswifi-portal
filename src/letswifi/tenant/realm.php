<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\tenant;

use DateInterval;
use DomainException;
use JsonSerializable;
use fyrkat\multilang\MultiLanguageString;
use fyrkat\openssl\X509;
use letswifi\configuration\Dictionary;

class Realm implements JsonSerializable
{
	/**
	 * @param array<string>   $serverNames
	 * @param array<X509>     $trust
	 * @param array<Network>  $networks
	 * @param array<Location> $location
	 */
	public function __construct(
		private readonly TenantConfig $tenantConfig,
		public readonly string $realmId,
		public readonly MultiLanguageString $displayName,
		public readonly array $serverNames,
		public readonly array $trust,
		public readonly DateInterval $validity,
		public readonly array $networks,
		public readonly ?MultiLanguageString $description = null,
		public readonly array $location = [],
		public readonly ?Logo $logo = null,
		public readonly ?string $contactId = null,
	) {
	}

	public static function fromConfig( TenantConfig $tenantConfig, Dictionary $realmData ): self
	{
		$location = $realmData->getDictionaryList( 'location' );
		$logo = $realmData->getDictionaryOrNull( 'logo' );

		return new self(
			tenantConfig: $tenantConfig,
			realmId: $realmData->getParentKey(),
			displayName: $realmData->getMultiLanguageString( 'display_name' ),
			serverNames: $realmData->getRawArray( 'server_names' ),
			trust: $tenantConfig->getCertificatesWithChain( ...$realmData->getRawArray( 'trust' ) ),
			validity: static::getValidity( $realmData->getInteger( 'validity' ) ),
			networks: $tenantConfig->getNetworks( ...$realmData->getRawArray( 'networks' ) ),
			location: \array_map( [Location::class, 'fromConfig'], $location ),
			logo: null === $logo ? null : Logo::fromConfig( $logo ),
			description: $realmData->getMultiLanguageStringOrNull( 'description' ),
			contactId: $realmData->getString( 'contact' ),
		);
	}

	/**
	 * @return array{realm_id:string,display_name:MultiLanguageString,description:?MultiLanguageString,contact:?Contact,location:array<Location>,logo:bool}
	 */
	public function jsonSerialize(): array
	{
		return [
			'realm_id' => $this->realmId,
			'display_name' => $this->displayName,
			'description' => $this->description,
			'contact' => $this->getContact(),
			'location' => $this->location,
			'logo' => isset( $this->logo ),
		];
	}

	public function getContact(): ?Contact
	{
		return null === $this->contactId ? null : $this->tenantConfig->getContact( $this->contactId );
	}

	protected static function getValidity( int|float|string|DateInterval $in ): DateInterval
	{
		if ( $in instanceof DateInterval ) {
			return $in;
		}
		if ( \is_float( $in ) ) {
			$in = (int)\round( $in );
		}
		if ( \is_int( $in ) && 0 < $in ) {
			return new DateInterval( "P{$in}D" );
		}
		if ( \is_string( $in ) ) {
			return DateInterval::createFromDateString( $in );
		}

		throw new DomainException( 'Invalid validity ' . $in );
	}
}
