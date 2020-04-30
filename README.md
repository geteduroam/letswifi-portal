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

Initialize the SQLite database

	make var/letswifi-dev.sqlite

Edit etc/letswifi.conf.php and change `userIdAttribute` to match your setup.

Write metadata of your SAML IdP to simplesamlphp/metadata/saml20-idp-remote.php

Navigate to https://example.com/simplesaml/module.php/saml/sp/metadata.php/default-sp?output=xhtml to get the metadata of the service, and register it in your IdP


## Running a development server

	make dev

### Doing a flow manually

* Open in your browser: http://[::1]:1080/oauth/authorize/?realm=example.com&response_type=code&code_challenge_method=S256&scope=testscope&code_challenge=E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM&redirect_uri=http://[::1]:1234/callback/&client_id=no.fyrkat.oauth&state=0
* Take note of the `code` in the response
* `code=… curl -id "grant_type=authorization_code&redirect_uri=http://[::1]:1234/callback/&client_id=no.fyrkat.oauth&code=$code&code_verifier=dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk" 'http://[::1]:1080/oauth/token/'`
* Take note of the `access_token` in the response
* `access_token=… curl -d '' -iHAuthorization:Bearer\ $access_token 'http://[::1]:1080/api/eap-config/'`
* If everything went fine, you get an eap-config XML payload
* You will see the public key material logged in the `tlscredential` SQL table


## Contributing

Before committing, please run

	make camera-ready
