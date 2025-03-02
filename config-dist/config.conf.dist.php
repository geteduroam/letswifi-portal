<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

return [
	// List of providers supported by this server.
	// The key must either be '_default', meaning any,
	// or the HTTP hostname used for the request.
	// It is neither supported or recommended to
	// use multiple hostnames for the same provider;
	// The wildcard is only provided for convenience.
	// If users will use different hostnames,
	// please set up redirects to the one canonical hostname.
	'provider' => [
		'_default' => [
			// The short name of the provider; multi-language,
			// Apps can show this description when the user chooses the
			// institution in one of the geteduroam apps.
			// REQUIRED: Array language => name
			'display_name' => [
				'en-GB' => 'Default provider',
				'nl-NL' => 'Standaard provider',
			],

			// Longer descriptive name of the provider; multi-language.
			// Apps can show this description when the user chooses the
			// institution in one of the geteduroam apps.
			// OPTIONAL: Array language => name
			'description' => [
				'en-GB' => 'The default provider',
				'nl-NL' => 'De standaard provider',
			],

			// List of realms that are available to this provider.
			// Used for access control; left hand is affiliation,
			// as reported by authentication module, right hand is list
			// of accessible realms.
			// The '' affiliation will always match.
			// If a user has multiple affiliations, they will have access to the
			// sum of realms available to these affiliations.
			// If a matching affilation has an empty list of realms,
			// further affiliations are no longer considered;
			// this can be used to lock out some affiliations.
			// If the user has access to multiple realms, they will be prompted
			// to select a realm when they attempt to generate a profile.
			// REQUIRED: Array affiliation => list of available realms
			'realm' => [
				// 'staff' => ['staff.example.com'],
				// 'student' => ['student.example.com'],
				'' => ['example.com'],
			],

			// Contact information for this provider
			// This may be shown to the user prior to selecting a realm,
			// and it will be available through public API.
			// After the user selects a realm,
			// the contact information from the realm is used.
			// REQUIRED: String, ID of contact
			'contact' => 'example.com',

			// Authentication configuration
			// Choose a authentication service and parameters.
			// The service must match one of the supported services,
			// param are the parameters provided to the service.
			// REQUIRED: Array service => string, param => array
			'auth' => [
				'service' => 'SimpleSamlAuth',
				'param' => [
					// A file to include for the SimpleSAMLphp autoloader.
					// If this is not provided SimpleSAMLphp must be loaded another way.
					// OPTIONAL: String path
					'autoloadInclude' => '/usr/local/share/simplesamlphp/lib/_autoload.php',

					// The auth source to use in SimpleSAMLphp.
					// Must match auth source configured in SimpleSAMLphp.
					// Required but set to default-sp if not provided or null.
					// REQUIRED: String auth source
					'authSource' => 'default-sp',

					// The user ID attribute returned by the IdP.
					// If not set, the NameID is used.
					// REQUIRED: null|String attribute name
					'userIdAttribute' => null,

					// The affiliation attribute returned by the IdP.
					// If not set, no affiliation for realm picking is used,
					// only the wildcard realm will match in this case.
					// OPTIONAL: String attribute name
					'affiliationAttribute' => 'eduPersonAffiliation',

					// Entity ID of the SAML IdP that the user must authenticate with.
					// See https://simplesamlphp.org/docs/stable/saml/sp.html, saml:idp
					// OPTIONAL: String attribute name
					'samlIdp' => null,

					// Entity IDs of SAML IdP that the user can authenticate with.
					// Used for scoping in federated SAML setups.
					// See https://simplesamlphp.org/docs/stable/saml/sp.html, IDPList
					// OPTIONAL: Array list of IdPs that user can authenticate with
					'idpList' => [],

					// Verify that the assertion was created by one of the idpList IdPs.
					// Generally a good idea, but might break with some simpler IdPs.
					// This should at least be on when authenticating against a federation.
					// OPTIONAL: Bool true to verify the IdP in the assertion
					'verifyAuthenticatingAuthority' => true,

					// A list of attributes that must be present in the SAML assertion
					// in order to allow authentication.
					// Use this for stricter SAML assertion checking.
					// Value can be a string or array of strings,
					// if it's an array of strings
					// at least one string must match per attribute.
					// OPTIONAL: Array attribute to expected value mapping
					'authzAttributeValue' => [],

					// Allowed home organisations
					// This is used when multiple organisations share a SAML IdP
					// OPTIONAL: Array of allowed values
					'allowedHomeOrgs' => [],

					// Attribute for the home organisation
					// Defaults to schacHomeOrganization
					// OPTIONAL: String home org attribute
					'homeOrgAttribute' => 'schacHomeOrganization',
				],
			],

			// Database for logging pseudocredentials and OAuth credentials
			// REQUIRED: Array containing dsn, username and password
			'pdo#inc' => 'database.conf.php',

			// List of OAuth clients that are allowed to use the API
			// REQUIRED: Array
			'clients#inc' => 'clients.conf.php',

			// OAuth shared secret
			// Create a new one with the following commands:
			// umask 337; head -c16 /dev/random | base64 | tr -d = >oauthsecret.txt
			// REQUIRED: String
			'oauthsecret#file' => 'oauthsecret.txt',

			// Location of the venue in lat/lon
			// This data, if provided, is included in the API and in eap-config files,
			// but currently it's not being used for anything.
			// OPTIONAL: Array with lat: float, lon: float
			// 'location' => [
			// 	['lat' => 52.0, 'lon' => 5.1], // Utrecht Centraal
			// ],
			'location' => [],

			// Logo for the provider
			// This will be displayed prominent in the apps
			// If no logo is to be set, omit the whole entry
			// OPTIONAL: Array with data: string and content_type: string
			// 'logo' => [
			// 	// The contents of the image file; it's recommended to instead use
			// 	// data#file and refer to a file instead
			// 	// If you don't want to set a logo, remove the whole logo,
			// 	// not just this entry; the logo is optional, but if you
			// 	// specify a logo, the data is required.
			// 	// REQUIRED: String
			// 	'data#file' => 'logo.png',
			// 	'data#file'=>null, // REMOVE THIS LINE
			//
			// 	// Content type, also known as MIME type; typically image/{png,jpeg},
			// 	// but image/svg+xml is also possible. Automatically detected
			// 	// from the file extension if you use data#file
			// 	// OPTIONAL: String
			// 	'content_type' => null,
			// ],
			'logo' => null,

			// The DN of the certificate that will be used for signing
			// apple-mobileconfig profiles.  This certificate does not need
			// to be a certificate authority, but it must be publicly trusted
			// in order to avoid MacOS/iOS from displaying the profile as
			// being unsigned and untrusted.
			// A normal, valid, server certificate for TLS suffices,
			// the hostname is not important.
			// OPTIONAL: String name of the certificate in the certificate list
			// 'profile-signer' => 'CN=…',
		],
	],

	// Optionally, you can read realms from a directory instead of configuring
	// them here.  This might be a good idea if you have a lot of realms.
	// Remove the whole `'realm' => [` block as well, otherwise it takes priority.
	// The following line reads realms from the realm directory
	// 'realm#dir' => 'realms/',

	// All realms managed by this server.
	// The realm is the part after the @ in the outer identity
	// The realm has information on how to identify the RADIUS server,
	// and how to generate a RADIUS credential.  Additionally,
	// it contains information on which network to connect to and helpdesk info.
	// Realms are accessible to users depending on the realm settings in the provider.
	'realm' => [
		// Example realm for staff
		'example.com' => [
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

			// Location of the venue in lat/lon
			// This data, if provided, is included in the API and in eap-config files,
			// but currently it's not being used for anything.
			// OPTIONAL: Array with lat: float, lon: float
			// 'location' => [
			// 	['lat' => 52.0, 'lon' => 5.1], // Utrecht Centraal
			// ],
			'location' => [],

			// Logo for the realm
			// This will be displayed prominent in the apps
			// If no logo is to be set, omit the whole entry
			// OPTIONAL: Array with data: string and content_type: string
			// 'logo' => [
			// 	// The contents of the image file; it's recommended to instead use
			// 	// data#file and refer to a file instead
			// 	// If you don't want to set a logo, remove the whole logo,
			// 	// not just this entry; the logo is optional, but if you
			// 	// specify a logo, the data is required.
			// 	// REQUIRED: String
			// 	'data#file' => 'logo.png',
			// 	'data#file'=>null, // REMOVE THIS LINE
			//
			// 	// Content type, also known as MIME type; typically image/{png,jpeg},
			// 	// but image/svg+xml is also possible. Automatically detected
			// 	// from the file extension if you use data#file
			// 	// OPTIONAL: String
			// 	'content_type' => null,
			// ],
			'logo' => null,
		],
	],

	// Wi-Fi network that the client must select
	'network' => [
		// Settings for the eduroam federated network
		'eduroam' => [
			// Name of the network, used for systems with named network profiles.
			// currently it's used for apple-mobileconfig and google-onc profiles.
			// If the profile has no localisation support, the current language when
			// the profile was generated decides the name being used in the profile.
			// REQUIRED: Array language => name
			'display_name' => [
				'en-GB' => 'eduroam',
				'nl-NL' => 'eduroam',
			],

			// SSID to configure for the network profile on the client.
			// At least one SSID or OID must be provided.
			// OPTIONAL: String for a single SSID
			'ssid' => 'eduroam',

			// OID to configure for the network profile on the client.
			// At least one SSID or OID must be provided.
			// OPTIONAL: Array of strings, containing hexadecimal OID
			'oid' => ['5a03ba0800'],
		],
	],

	// Contact information for the helpdesk
	// PLEASE NOTE: Information entered here is publicly viewable
	// These are referenced by realms and providers, and provide users with
	// contact information to a helpdesk where they can get help with their
	// connection and their devices.  This contact information is publicly
	// available through the API, and will be shown in the apps and might
	// also be shown on the web portal.
	'contact' => [
		// Free-text key, must be referenced from provider or realm
		'example.com' => [
			// E-mail address for support
			// String e-mail address
			'mail' => 'contact@example.com',
			'mail' => null, // REMOVE THIS LINE

			// Website address for support
			// OPTIONAL: Array language => name
			'web' => 'https://support.example.com',
			'web' => null, // REMOVE THIS LINE

			// Website address for support
			// OPTIONAL: Array language => name
			'phone' => '+1555eduroam',
			'phone' => null, // REMOVE THIS LINE
		],
	],

	// Settings for branding of the application.
	// This will change some strings in the user interface,
	// as well as make some visual changes
	// Additionally, it contains the list of available apps and profiles,
	// and the links to these apps, as these can also be brand dependent.
	// Find the appropriate branding file in config-dist and copy it here.
	'branding#inc' => 'branding.conf.php',

	// Location where to store certificates.
	// These are used for signing CA, trusted CAs for the RADIUS servers
	// and certificates used for code signing apple-mobileconfig profiles.
	// The #pemdir part makes it so that certificates are retrieved from the
	// directory configured, instead of listing all certificate material inline.
	// REQUIRED: Array x509, key, issuer
	'certificate#pemdir' => 'certs/',
];
