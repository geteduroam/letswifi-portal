<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

return [
	// Shell script test application
	'app.geteduroam.sh' => [
		'clientId' => 'app.geteduroam.sh',
		// We use mainly IPv4 because the nc binary might not use IPv6 by default
		'redirectUris' => ['http://[::1]/', 'http://127.0.0.1/'],
		'scopes' => ['eap-metadata'],
		'refresh' => true,
	],

	// Windows application
	// https://github.com/geteduroam/windows-app
	'app.geteduroam.win' => [
		'clientId' => 'app.geteduroam.win',
		// Windows supports IPv6 just fine, so the IPv4 version might not be needed
		'redirectUris' => ['http://[::1]/', 'http://127.0.0.1/'],
		'scopes' => ['eap-metadata'],
		'refresh' => true,
	],

	// Mobile application
	// https://github.com/geteduroam/ionic-app/pull/47
	'app.eduroam.geteduroam' => [
		'clientId' => 'app.eduroam.geteduroam',
		'redirectUris' => ['app.eduroam.geteduroam:/'],
		'scopes' => ['eap-metadata'],
	],
];
