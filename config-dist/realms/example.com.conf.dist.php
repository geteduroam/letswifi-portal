<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

return [
	// The short name of the realm; multi-language,
	// at least one language must be provided.
	// Will be used as title in choice menus, profile installation, etc.
	// REQUIRED: Array language => name
	'display_name' => [
		'en-GB' => 'Example',
		'nl-NL' => 'Voorbeeld',
	],

	// Longer descriptive name of the realm; multi-language.
	// at least one language must be provided.
	// Will be used as title in choice menus, profile installation, etc.
	// OPTIONAL: Array language => name
	'description' => [
		'en-GB' => 'The example realm',
		'nl-NL' => 'De voorbeeldrealm',
	],

	// The client requires that the RADIUS server presents a certificate
	// containing at least one of these server names.
	// For old Android versions that don't support multiple server names,
	// the longest common suffix is used instead;
	// because of this it is recommend to keep all server names within the same domain.
	// REQUIRED: Array, list of server names, at least one
	'server_names' => ['radius.example.com'],

	// Signing certificate authority
	// This CA must have both certificate and private key available
	// REQUIRED: String, name of the signer CA
	'signer' => 'CN=example.com Let\'s Wi-Fi CA',

	// Trusted certificate authorities
	// This can be more than one; server certificate must be signed by one CA
	// Private key does not need to be present, but then the RADIUS server
	// must be provisioned another way, such as through an ACME provider
	// REQUIRED: Array, list of trusted CAs
	'trust' => ['C=US, O=Let\'s Encrypt, CN=R11', 'CN=example.com Let\'s Wi-Fi CA'],

	// When signing the client certificate credential,
	// set the validity this many days in the future
	// REQUIRED: Integer, number of days of validity
	'validity' => 365,

	// Contact information for this realm
	// When the user has selected a realm and downloads a profile file,
	// this contact information may be present in the file.
	// When using an app, it may show this information prior to connecting,
	// and when re-launching the app after the profile has been configured.
	// REQUIRED: String, ID of contact
	'contact' => 'example.com',

	// List of Wi-Fi networks to configure on the clients
	// These can be SSID and HS20 networks.
	// Currently there is no way to let users opt-in or opt-out from networks,
	// you may use different realms for this if it is desireable.
	// The names of the networks must match the network ID in this configuration
	// REQUIRED: Array, list of network IDs
	'networks' => ['eduroam'],

	// Logo for the realm
	// This will be displayed prominent in the apps
	// If no logo is to be set, omit this whole entry
	// OPTIONAL: Array with data: string and content_type: string
	// 'logo' => [
	// The contents of the image file; it's recommended to instead use
	// data#file and refer to a file instead
	// REQUIRED: String
	// 'data#file' => 'logo.png',

	// Content type, also known as MIME type; typically image/{png,jpeg},
	// but image/svg+xml is also possible. Automatically detected
	// from the file extension if you use data#file
	// OPTIONAL: String
	// 'content_type' => null,
	// ],
];
