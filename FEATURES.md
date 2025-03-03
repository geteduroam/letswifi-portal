# Let's Wi-Fi portal features

The portal provides users a landing page to configure their device.
We recommend configuring through an app, the portal provides an OAuth 2 API supported by multiple apps listed on https://eduroam.app.

The portal authenticates users with SimpleSAMLphp.
No user profiles are created, so apart from the logging of certificate creation, authentication does not leave a trace in the application.

## New API

The portal provides endpoints to apps through a new API,
so apps can find contact information, logo and different configuration profiles (currently only eap-config and apple-mobileconfig).
This behaviour is required for apps since discovery v2 (currently v3),
but for the near future we have a compatibility layer in the discovery service
so that older versions of this software will keep working with the newer apps.

This compatibility layer runs independent from this software;
it's part of the discovery service at discovery.eduroam.app.

## Users and revocation

When logging in, the user has access to a page listing all their OAuth2 grants eligible for refreshing and listing all their certificates.
These can also be self-revoked in the new UI,
but note that this revocation must also be checked in on RADIUS server.
Integration for this can be implemented by reading the database from the RADIUS server, a later version will provide an API for this.

A later version will also provide an API for programmatically revoking users by username.

## Platforms and profiles

For platforms where no app is available, configuration profiles can be made, such as Apple mobileconfig for MacOS and ONC profiles for ChromeOS.
These options are clearly listed on the landing page if the platform requires this,
and are available with one additional click for other platforms, as these are presented the app as the primary option.

## Multirealm and multitenant configuration files

The configuration file allows to define multiple providers (e.g. institutions) and multiple realms per provider, allowing a national roaming operator to just run one instance of the portal to serve many providers at once.

The realms can contain contact information and logo which are shown in the apps,
making sure that users can easily get help from their own provider,
as well as technical information such as trusted CA, signing CA and validity of pseudo-credentials.
These can be configured independently for every realm.

Realms are accesible based on SAML attributes.
If multiple realms are available, the user is presented a realm chooser.

## Multilanguage

All user-presented strings are translatable using a locale file,
and all realms and providers are multi-lingual as well.
In the web interface, the [Accept-Language](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Accept-Language) header is used to determine the most appropriate language.
In the apps, all languages are provided to the app so it can use its app-specific facilities to show all names in the most appriopriate language.

## Certificate containers

Certificates are provided in PKCS12 containers, these are encrypted with 3DES for maximum compatibility. The files are not encrypted with a strong passphrase, but they have to be encrypted because some platforms refuse to read them otherwise.

## Power users

At the bottom of the landing page, power users can open a toggle and follow a link to a more advanced page for certificate generation.
On this page, they can set a passphrase for PKCS12 payloads and download the payload in every format supported by the software.
