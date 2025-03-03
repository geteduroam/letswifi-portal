# Deployment considerations

The software can be installed for a single provider, or for a roaming operator serving multiple operators from a single instance.
This can be configured in the **letswifi.conf.php** configuration file.
A **provider** is an organisation such as a school, university, government instance etc.,
a **realm** is used for user groups.

If you anticipate that you will have many providers or realms,
you can set `'provider#dir' => 'providers/'` and/or `'realm#dir' => 'realms/'`,
so that you can manage providers and realms by having one file for each.
That way, you can avoid large config files.


## Requirements

This software is intended to be used as part of a larger system.
It can be installed and run standalone, but it won't be very useful apart from testing purposes.

For a functional setup at least the following is needed:

* SAML IdP metadata, and possibility to add our own SP metadata to the identity provider
* 802.1x Wi-Fi network
* RADIUS server that supports EAP-TLS serving the 802.1x network

The SAML IdP is needed to support authentication, without an authentication method no certificates can be issued.
Without a functioning 802.1x enabled Wi-Fi network the software will work as intended,
but clients won't be able to connect.
This is identical to the situation where a user sets up Wi-Fi while out of range, for example when at home.

The software uses a database to log issued certificates,
and to allow revoking these certificates.
Currently SQLite and MariaDB/MySQL are supported as databases.

### RADIUS

Realms configured in this software must match the realms being configured in the RADIUS server.
How to configure a RADIUS server is out of scope, but the server certificate in the RADIUS server
and the configured trusted certificate in the configuration for letswifi-portal must match,
and the RADIUS server must trust the CA used to issue client certificates.

## Uptime and redundancy

This software is only used by the user to obtain a certificate.
After the certificate is obtained, the user only needs the RADIUS server and Wi-Fi infrastructure to be able to connect,
so downtime of this application will only affect new users but not enrolled users.

Nevertheless, the software can be run in a redundant fashion;
letswifi-portal by itself will write all state in the database,
so it's possible to run multiple instances behind a load balancer.

However, SimpleSAMLphp uses PHP sessions by default, which are not synced between instances.
If you want to run with multiple instances behind a load balancer,
make sure you use sticky sessions on your load balancer, or
[configure SimpleSAMLphp with a different session store](https://simplesamlphp.org/docs/stable/simplesamlphp-maintenance.html#session-management)
that's shared between instances.
