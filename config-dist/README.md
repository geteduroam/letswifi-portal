# Let's Wi-Fi configuration

The application reads the configuration file `letswifi.conf.php`,
example files are provided for development and production.
There's inline documentation in the example configuration files.

This `config-dist` directory is not used at all by the application.
Instead, it expects all files to be in a `config` directory,
which you may need to create yourself.
You can copy the `.conf.dist.php` example files from here,
put them in `config` and rename them to `.conf.php`.


## Multilingual

All textual fields are multi-language.
The application will choose the most appropriate language from the users'
Accept-Language header, if no appropriate language is found, it will use the first one in the list (PHP dictionary arrays differ from JSON dictionaries in that they keep ordering).


## Large configurations

When you have a large setup, you might want to manage your configuration instead of updating it manually.
It's possible to replace the PHP dictionary with a filesystem directory, like this:

Instead of doing this:

```php
<?php return [
	'realms' => [
		'example.com' => [/* ... */],
		'student.example.com' => [/* ... */],
		'staff.example.com' => [/* ... */],
		/* ... */
];
```

you can do this:

```php
<?php return [
	'realms#dir' => 'realms/',
];
```

and then you can put files such as `example.com.conf.php` in the `realms/` directory.
All files must start with `<?php return [`,
comments are allowed, also before the `return` statement.


## Provider

There can be one or more providers configured in the configuration file.
Providers are identified by a single hostname, although for convenience
it's possible to set `_default` which would match any hostname.
This is not recommended unless you only set a single provider.

Providers contain information about how to authenticate users,
and which realms are available and to whom.
Access to realms is restricted by user attributes,
if a user has access to more than one realm they will be prompted which realm to use.
Therefore it's recommended to set clear names for your realms so users choose the correct one.
The name, description and contact information for the provider will be shown in the apps,
before the user authenticates.
Therefore, make sure that the helpdesk information is useful for users that have authentication problems.

Additionally, some technical settings are set on the provider level, such as OAuth key, allowed OAuth clients, database and Apple mobileconfig signer certificate (optional).


## Realm

Realms indicate the domain behind the `@` in the 802.1x identity when the user connects to the Wi-Fi.
The realm configuration contains all technical information needed to create a Wi-Fi profile,
such as signer for the EAP-TLS credential,
trusted CA to verify the RADIUS server certificate,
how long the EAP-TLS credential must be valid for,
and which Wi-Fi networks to configure (such as eduroam, OpenRoaming, etc.)

Additionally, the realm also contains contact information.
This will be shown in the apps after the user authenticated,
and it might be shown in the apps when the user re-opens the app after succesful configuration.
Please be aware that the information is retrieved by the app at initial setup,
not necessarily upon every subsequent launch.


## Contact information

Contact information is identified by an identifier which does not require a specific format.
The identifier is referred from providers and realms, and can be referred more than once.

Contact information may consist of a web address, phone number and e-mail address.
This information is shown in the apps and is publicly retrievable using the API.
Contact information may also contain a logo and list of latitude/longitude locations.
Currently the location data is not being used, but it is provided via the API,
and embedded in configuration profiles that have support for location data.
