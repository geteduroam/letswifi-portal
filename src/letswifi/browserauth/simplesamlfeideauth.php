<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2020, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\browserauth;

class SimpleSAMLFeideAuth extends SimpleSAMLAuth
{
	/** @var ?string */
	private $feideHomeOrg;

	public function __construct( array $params )
	{
		parent::__construct( $params );
		$this->feideHomeOrg = \array_key_exists( 'feideHomeOrg', $params ) ? $params['feideHomeOrg'] : null;
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
			\header( 'Location: https://idp.feide.no/simplesaml/module.php/feide/preselectOrg.php?' . \http_build_query( ['HomeOrg' => $this->feideHomeOrg, 'ReturnTo' => $loginUrl] ) );
			exit;
		}

		return parent::requireAuth();
	}
}
