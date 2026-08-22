<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\profile;

use DateInterval;
use DomainException;
use JsonSerializable;
use fyrkat\configmap\Dictionary;
use fyrkat\multilang\MultiLanguageString;
use fyrkat\openssl\X509;

class Realm implements JsonSerializable
{
	/**
	 * @param array<string>        $serverNames
	 * @param array<string>        $trust
	 * @param array<Network>       $networks
	 * @param array<Location>      $location
	 * @param array<string,string> $extra
	 */
	public function __construct(
		private readonly ProfileService $profileService,
		public readonly string $realmId,
		public readonly MultiLanguageString $displayName,
		public readonly array $serverNames,
		public readonly array $trust,
		public readonly string $signer,
		public readonly DateInterval $validity,
		public readonly array $networks,
		public readonly ?MultiLanguageString $description = null,
		public readonly array $location = [],
		public readonly ?Logo $logo = null,
		public readonly ?string $contactId = null,
		public readonly array $admins = [],
		public readonly array $extra = [],
	) {
		$serverNames || throw new DomainException( "Realm {$realmId}: server_names cannot be empty" );
		$trust || throw new DomainException( "Realm {$realmId}: trust cannot be empty" );
		$networks || throw new DomainException( "Realm {$realmId}: networks cannot be empty" );
	}

	public function getExtra( string $extra ): ?string
	{
		return $this->extra[$extra] ?? null;
	}

	public static function fromConfig( ProfileService $profileService, Dictionary $realmData ): self
	{
		$location = $realmData->getDictionaryList( 'location' );
		$logo = $realmData->getDictionaryOrNull( 'logo' );

		return new self(
			profileService: $profileService,
			realmId: $realmData->getParentKey(),
			displayName: $realmData->getObject( 'display_name', MultiLanguageString::class ),
			serverNames: $realmData->getStrings( 'server_names' ),
			trust: $realmData->getStrings( 'trust' ),
			signer: $realmData->getString( 'signer' ),
			validity: static::getValidity( $realmData->getInt( 'validity' ) ),
			networks: $profileService->getNetworks( ...$realmData->getStrings( 'networks' ) ),
			location: \array_map( [Location::class, 'fromConfig'], $location ),
			logo: null === $logo ? null : Logo::fromConfig( $logo ),
			description: $realmData->getObjectOrNull( 'description', MultiLanguageString::class ),
			contactId: $realmData->getStringOrNull( 'contact' ),
			admins: $realmData->has( 'admins' ) ? $realmData->getStrings( 'admins' ) : [],
			extra: \array_filter( [
				'mobileconfig_identifier' => $realmData->getStringOrNull( 'mobileconfig_identifier' ),
				'mobileconfig_display_name' => $realmData->getStringOrNull( 'mobileconfig_display_name' ),
			] ),
		);
	}

	/**
	 * @return array<X509>
	 */
	public function getTrustedCACertificates(): array
	{
		return $this->profileService->getCertificatesWithChain( ...$this->trust );
	}

	public function getSignerCertificate(): X509
	{
		return $this->profileService->getCertificate( $this->signer );
	}

	/**
	 * @return array{realm_id:string,display_name:MultiLanguageString,description:?MultiLanguageString,contact:?Contact,location:array<Location>,logo:bool,signer:string,trust:array<string>,networks:array<string,array{oids?:array<string>,nai_realms?:array<string>,ssid?:string,display_name:MultiLanguageString}>}
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
			'signer' => $this->signer,
			'trust' => $this->trust,
			'networks' => \array_reduce( $this->networks, static fn( array $carry, Network $network ): array => [
				$network->networkId => ['display_name' => $network->displayName]
				+ ( $network instanceof NetworkPasspoint
					? ['oids' => $network->oids, 'nai_realms' => $network->naiRealms] : [] )
				+ ( $network instanceof NetworkSSID
					? ['ssid' => $network->ssid] : [] )
				+ ( $carry[$network->networkId] ?? [] ),
			] + $carry, [] ),
		];
	}

	public function getContact(): ?Contact
	{
		return null === $this->contactId ? null : $this->profileService->getContact( $this->contactId );
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
			if ( $result = DateInterval::createFromDateString( $in ) ) {
				return $result;
			}
		}

		throw new DomainException( 'Invalid validity ' . $in );
	}
}
