# Let's Wifi Certificate Authority

This is the reference CA for geteduroam.  It is intended to be used with an app such as [ionic-app](https://github.com/geteduroam/ionic-app).  The process is as follows:

* The app sends the user to /oauth/authorize/ with additional GET parameters
* The user is asked to log in or redirected to an SSO service
* After logging in, the user is redirected to a callback URL from the app
* The app has obtained an authorization_code, which it uses to retrieve an access_code
* The access_code is used to generate an [eap-config](https://tools.ietf.org/html/draft-winter-opsawg-eap-metadata-02) file containing user credentials
* The app installs the eap-config file
* The server logs the public key material generated


## Getting up and running quick 'n dirty

This quick'n'dirty guide assumes you'll be using SimpleSAMLphp (the only authentication method supported ATM)

	make simplesamlphp

Write metadata of your SAML IdP to simplesamlphp/metadata/saml20-idp-remote.php

Navigate to https://example.com/simplesaml/module.php/saml/sp/metadata.php/default-sp?output=xhtml to get the metadata of the service, and register it in your IdP


## Running a development server

	make dev


## Contributing

Before committing, please run

	make camera-ready
