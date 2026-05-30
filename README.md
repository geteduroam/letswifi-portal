# Let's Wifi Certificate Authority

This is the reference CA for geteduroam.  It is intended to be used with an app such as [ionic-app](https://github.com/geteduroam/ionic-app).  The process is as follows:

* The app sends the user to /oauth/authorize/ with additional GET parameters
* The user is asked to log in or redirected to an SSO service
* After logging in, the user is redirected to a callback URL from the app
* The app has obtained an authorization_code, which it uses to retrieve an access_code
* The access_code is used to generate an [eap-config](https://tools.ietf.org/html/draft-winter-opsawg-eap-metadata-02) file containing user credentials
* The app installs the eap-config file
* The server logs the public key material generated


## Install dependencies

In order to automatically install dependencies, run:

	make vendor


## Running a development server

	rm -rf etc/letswifi.conf.php var
	make dev

The realm being used is `example.com`


### Testing manually

There is a [shell script to initiate an OAuth flow](https://github.com/geteduroam/geteduroam-sh)

	./geteduroam.sh 'http://localhost:1080' example.com >test.eap-config

* If everything went fine, you get an eap-config XML payload in test.eap-config
* You will see the public key material logged in the `tlscredential` SQL table


## Getting up and running quick 'n dirty

Upload this whole project to a webserver, and make `www/` accessible as the top level directory.

This quick'n'dirty guide assumes you'll be using SimpleSAMLphp (the only authentication method supported for production)

	make SIMPLESAMLPHP_VERSION=2.2.6 simplesamlphp

Initialize the SQLite database (MySQL is also supported, this should be straightforward from the config file)

	mkdir var
	sqlite3 var/letswifi-dev.sqlite <sql/letswifi-dev.sqlite.sql

Copy etc/letswifi.conf.simplesaml.php etc/letswifi.conf.php and change `userIdAttribute` to match your setup.

	cp etc/letswifi.conf.simplesaml.php etc/letswifi.conf.php

Create the realm with a default client certificate validity of one year

	bin/add-realm.php example.com 365

## Configuring simplesamlphp

    cp simplesamlphp/config/authsources.php.dist simplesamlphp/config/authsources.php

Edit `simplesampphp/config/authsources.php`

Set  `'entityID'` (usually this is the URL for the webserver `https://eample.com`)

Request the metadata for our SAML IdP from your Identity and Access Management team.

    cp simplesamlphp/metadata/saml20-idp-remote.php.dist  simplesamlphp/metadata/saml20-idp-remote.php

Edit`simplesamlphp/metadata/saml20-idp-remote.php` and add the metadata from your IAM team.

The format of the file can be found: [File format info](https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-remote)

    cp simplesamlphp/config/config.php.dist simplesamlphp/config/config.php

Edit `simplesamlphp/config/config.php`

Modify `'cachedir' => '/var/cache/simplesamlphp'` - Remove the `/` from `/var`

Navigate to [https://example.com/simplesaml/module.php/saml/sp/metadata.php/default-sp?output=xhtml] to get the metadata of the service, and register it in your IdP

## Running from a subdirectory

For some deployments it can make sense to run this project from a subdirectory.
All paths inside the application are relative, so this should work without any issues.


## Contributing

Before committing, please run

	make camera-ready
