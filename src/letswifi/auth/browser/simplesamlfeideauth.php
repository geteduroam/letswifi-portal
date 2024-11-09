<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\auth\browser;

use DomainException;

class SimpleSAMLFeideAuth extends SimpleSAMLAuth
{
	/**
	 * @psalm-suppress InvalidArgument
	 * @psalm-suppress ArgumentTypeCoercion
	 *
	 * @suppress PhanParamTooFewUnpack
	 */
	public function __construct(
		public readonly ?string $feideHomeOrg = null,
		?string $feideOrgAttribute = null,
		public readonly string $feideHostname = 'idp.feide.no',
		array ...$rest,
	) {
		if ( $feideHomeOrg ) {
			if ( \array_key_exists( 'allowedHomeOrgs', $rest ) ) {
				throw new DomainException( 'Cannot set both allowedHomeOrgs and feideHomeOrg' );
			}
			$rest['allowedHomeOrgs'] = [$feideHomeOrg];
		}
		if ( $feideOrgAttribute && !\array_key_exists( 'homeOrgAttribute', $rest ) ) {
			$rest['homeOrgAttribute'] = $rest['feideOrgAttribute'];
		}
		parent::__construct( ...$rest );
	}

	/**
	 * Redirect user to login page, possibly through the Feide organisation preselector
	 *
	 * In Feide we cannot simply send the list of allowed identity providers;
	 * this list is instead on the IdP side of things.
	 * So instead, we redirect the user to a Feide-specific service that preselect the correct IdP,
	 * and then redirects back to our own login URL, which then will redirect to Feide,
	 * where the correct IdP then should be selected.
	 *
	 * @suppress PhanUndeclaredClassMethod We don't have a dependency on SimpleSAMLphp
	 *
	 * @psalm-suppress UndefinedClass We don't have a dependency on SimpleSAMLphp
	 */
	public function requireAuth(): string
	{
		if ( $this->feideHomeOrg && !$this->as->isAuthenticated() ) {
			$loginUrl = $this->as->getLoginURL();
			if ( null !== $this->samlIdp ) {
				$loginUrl .= '&saml%3Aidp=' . \urlencode( $this->samlIdp );
			}
			\header( 'Location: https://' . $this->feideHostname . '/simplesaml/module.php/feide/preselectOrg.php?' . \http_build_query( ['HomeOrg' => $this->feideHomeOrg, 'ReturnTo' => $loginUrl] ) );

			exit;
		}

		return parent::requireAuth();
	}
}
