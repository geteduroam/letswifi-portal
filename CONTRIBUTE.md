# Contributing

## Setting up a development environment

Set up a simple development server with

	make dev

This will write an SQLite database in `var/` and copy appropriate development configuration files to `etc/`,
and start a PHP server on port 1080.
You are automatically authenticated when visiting `http://localhost:1080` with the same username that you use on your development system, but you can change the username in the config file if you want.

Pseudocredentials issued by the development server cannot be used to connect to a network,
as the certificates used were generated locally when you started the server for the first time.


### Testing OAuth flow

There is a [shell script to initiate an OAuth flow](https://github.com/geteduroam/geteduroam-sh), which is based off a [commandline OAuth2 PKCE client](https://git.sr.ht/~jornane/oauth-sh/).

	./geteduroam.sh 'http://[::1]:1080' example.com >test.eap-config

* If everything went fine, you get an eap-config XML payload in test.eap-config
* You will see the public key material logged in the `realm_signing_log` SQL table


## Creating a pull request

Create a fork on the same platform where you find this code,
and send us the patch using the appropriate method of the platform.

Before committing, please run

	make camera-ready

And only commit if there are no errors.
This will test your code for common errors and ensure you follow the style guide.
