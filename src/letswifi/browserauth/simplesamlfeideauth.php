<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2020, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\browserauth;

use Throwable;

class SimpleSAMLFeideAuth extends SimpleSAMLAuth
{
	/** @var ?string */
	private $feideHomeOrg;

	/** @var string */
	private $feideOrgAttribute;

	/** @var string */
	private $feideHostname;

	public function __construct( array $params )
	{
		parent::__construct( $params );
		$this->feideHomeOrg = \array_key_exists( 'feideHomeOrg', $params ) ? $params['feideHomeOrg'] : null;
		$this->feideOrgAttribute = \array_key_exists( 'feideOrgAttribute', $params ) ? $params['feideOrgAttribute'] : 'schacHomeOrganization';
		$this->feideHostname = \array_key_exists( 'feideHostname', $params ) ? $params['feideHostname'] : 'idp.feide.no';
	}

	/**
	 * @psalm-suppress UndefinedClass We don't have a dependency on SimpleSAMLphp
	 * @suppress PhanUndeclaredClassMethod We don't have a dependency on SimpleSAMLphp
	 */
	public function requireAuth(): string
	{
		if ( null !== $this->feideHomeOrg && !$this->as->isAuthenticated() ) {
			$loginUrl = $this->as->getLoginURL();
			if ( null !== $this->samlIdp ) {
				$loginUrl .= '&saml%3Aidp=' . \urlencode( $this->samlIdp );
			}
			\header( 'Location: https://' . $this->feideHostname . '/simplesaml/module.php/feide/preselectOrg.php?' . \http_build_query( ['HomeOrg' => $this->feideHomeOrg, 'ReturnTo' => $loginUrl] ) );
			exit;
		}

		$result = parent::requireAuth();

		if ( isset( $this->feideHomeOrg ) ) {
			$org = $this->getSingleAttributeValue( $this->feideOrgAttribute );
			if ( $org !== $this->feideHomeOrg ) {
				throw new MismatchFeideException( $this->feideHomeOrg, $org );
			}
		}

		return $result;
	}

	public function guessRealm( array $params ): ?string
	{
		foreach ( $params as $candidate => $p ) {
			try {
				if ( \array_key_exists( 'feideOrgAttribute', $p ) && \array_key_exists( 'feideHomeOrg', $p ) ) {
					if ( $this->getSingleAttributeValue( $p['feideOrgAttribute'] ) === $p['feideHomeOrg'] ) {
						return $candidate;
					}
				}
			} catch ( Throwable $_ ) {
				/* we're guessing so no need to handle */
			}
		}

		return parent::guessRealm( $params );
	}
}
