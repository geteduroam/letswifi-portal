<?php return [
	// List of providers supported by this server.
	// The key must either be '*', meaning any,
	// or the HTTP hostname used for the request.
	// It is neither supported or recommended to
	// use multiple hostnames for the same provider;
	// The wildcard is only provided for convenience.
	// If users will use different hostnames,
	// please set up redirects to the one canonical hostname.
	'provider' => [
		'*' => [
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
			// The * affiliation will always match.
			// If a user has multiple affiliations, they will have access to the
			// sum of realms available to these affiliations.
			// If a matching affilation has an empty list of realms,
			// further affiliations are no longer considered;
			// this can be used to lock out some affiliations.
			// If the user has access to multiple realms, they will be prompted
			// to select a realm when they attempt to generate a profile.
			// REQUIRED: Array affiliation => list of available realms
			'realm' => [
				'staff' => ['staff.example.com'],
				'student' => ['student.example.com'],
				'*' => ['example.com']
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
				// DevAuth only works with the PHP CLI server
				'service' => 'DevAuth',
				'param' => [
					'username' => \get_current_user(),
					'affiliations' => ['staff', 'student', 'employee'],
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

			// The DN of the certificate that will be used for signing
			// apple-mobileconfig profiles.  This certificate does not need
			// to be a certificate authority, but it must be publicly trusted
			// in order to avoid MacOS/iOS from displaying the profile as
			// being unsigned and untrusted.
			// A normal, valid, server certificate for TLS suffices,
			// the hostname is not important.
			// OPTIONAL: String name of the certificate in the certificate list
			'profile-signer' => 'CN=demo.letswifi.eu',
		],
	],

	// All realms managed by this server.
	// The realm is the part after the @ in the outer identity
	// The realm has information on how to identify the RADIUS server,
	// and how to generate a RADIUS credential.  Additionally,
	// it contains information on which network to connect to and helpdesk info.
	// Realms are accessible to users depending on the realm settings in the provider.
	'realm#dir' => 'realms/',

	// Wi-Fi network that the client must select
	'network' => [
		// Settings for the eduroam federated network
		'eduroam' => [
			// Name of the network, used for systems with named network profiles.
			// currently it's used for apple-mobileconfig and google-onc profiles.
			// If the profile has no localisation support, the current language when
			// the profile was generated decides the name being used in the profile.
			// REQUIRED: Array language => name
			'display_name' => ['en-GB' => 'eduroam'],

			// SSID to configure for the network profile on the client.
			// At least one SSID or OID must be provided.
			// OPTIONAL: String for a single SSID
			'ssid' => 'eduroam',

			// OID to configure for the network profile on the client.
			// At least one SSID or OID must be provided.
			// Not supported for Google-ONC profiles.
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

			// Website address for support
			// OPTIONAL: Array language => name
			'web' => 'https://support.example.com',

			// Website address for support
			// OPTIONAL: Array language => name
			'phone' => '+1555eduroam',

			// Location of the venue in lat/lon
			// This data, if provided, is included in the API and in eap-config files,
			// but currently it's not being used for anything.
			// OPTIONAL: Array with lat: float, lon: float
			'location' => [
				['lat' => 52.0, 'lon' => 5.1], // Utrecht Centraal
			],

			// Logo for the provider or realm
			// This will be displayed prominent in the apps
			// If no logo is to be set, omit the whole entry
			// OPTIONAL: Array with data: string and content_type: string
			'logo' => [
				// The contents of the image file; it's recommended to instead use
				// data#file and refer to a file instead
				// REQUIRED: String
				'data#file' => 'logo.jpg',

				// Content type, also known as MIME type; typically image/{png,jpeg},
				// but image/svg+xml is also possible. Automatically detected
				// from the file extension if you use data#file
				// OPTIONAL: String
				'content_type' => null,
			],
		],
	],

	'certificate#pemdir' => 'certs/',
];
