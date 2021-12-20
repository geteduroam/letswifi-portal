<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2021, Jørn Åne de Jong, Uninett AS <jornane.dejong@surf.nl>
 * Copyright: 2020-2021, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\browserauth;

use Throwable;

class SimpleSAMLFeideAuth extends SimpleSAMLAuth
{
	/** @var string */
	private $feideHostname;

	public function __construct( array $params )
	{
		parent::__construct( $params );
		$feideHostname = $params['feideHostname'] ?? 'idp.feide.no';
		\assert( \is_string( $feideHostname ), 'feideHostname must be string' );
		$this->feideHostname = $feideHostname;
	}

	/**
	 * @suppress PhanUndeclaredClassMethod We don't have a dependency on SimpleSAMLphp
	 */
	public function requireAuth(): string
	{
		if ( null !== $this->allowedHomeOrg && !$this->as->isAuthenticated() ) {
			$loginUrl = $this->as->getLoginURL();
			if ( null !== $this->samlIdp ) {
				$loginUrl .= '&saml%3Aidp=' . \urlencode( $this->samlIdp );
			}
			\header( 'Location: https://' . $this->feideHostname . '/simplesaml/module.php/feide/preselectOrg.php?' . \http_build_query( ['HomeOrg' => $this->allowedHomeOrg, 'ReturnTo' => $loginUrl] ) );
			exit;
		}

		return parent::requireAuth();
	}

	public function guessRealm( array $params ): ?string
	{
		foreach ( $params as $candidate => $p ) {
			try {
				if ( \array_key_exists( 'homeOrgAttribute', $p ) && \array_key_exists( 'allowedHomeOrg', $p ) ) {
					if ( $this->getSingleAttributeValue( $p['homeOrgAttribute'] ) === $p['allowedHomeOrg'] ) {
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
