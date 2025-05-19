# Installation

This will guide you through the steps required to install letswifi-portal and SimpleSAMLphp in a production setting.
Instructions for SQLite and MySQL are provided, but the installation and set-up of a MySQL server is out of scope.
For SQLite no database server is needed.
Setting up RADIUS and Wi-Fi is out of scope for this document.

This document describes how to do the installation manually.
There may be easier installation methods available.


## Before you begin

### Deployment considerations

Before installing the software, please read the [deployment considerations](DEPLOY.md)
and make sure you know how you want to setup the software.
This document will provide you with instructions how to set up lets

## Requirements

The software can run on any platform with PHP 8.1 or newer,
but it has been tested and is known to work on:

<details><summary>Debian Linux 12 / Ubuntu 22+</summary>

```sh
apt-get install \
	curl cron ca-certificates \
	git \
	php-fpm php-dom php-sqlite3 php-mbstring php-curl composer \
	apache2 \
	sqlite3
a2enmod proxy_fcgi setenvif
a2enconf "$(basename /etc/apache2/conf-available/php*-fpm.conf)"
a2dismod status
systemctl restart apache2
```
</details>
<details><summary>FreeBSD 14</summary>

```sh
pkg install \
	apache24 \
	git-lite \
	php84-dom php84-sqlite3 php84-curl php84-mbstring composer \
	sqlite3
```
</details>


## Web Server

You can run Let's Wi-Fi on the Apache web server,
but lighttpd and nginx are also supported.
We have not tried running it with IIS.

Installation and configuration of the web server should be straightforward.

Remember to enable HTTPS.

<details><summary>Example Apache Vhost</summary>

```html
<VirtualHost *:443>
	ServerName  	example.com
	DocumentRoot	/usr/local/share/letswifi-portal/htdocs
	Alias       	/simplesaml	/usr/local/share/simplesamlphp/public

	SetEnv	SIMPLESAMLPHP_CONFIG_DIR	/etc/simplesamlphp
	SetEnv	LETSWIFI_CONFIG_DIR     	/etc/letswifi

	SSLEngine              	on
	SSLCertificateFile     	/etc/ssl/certs/ssl-cert-snakeoil.pem
	SSLCertificateKeyFile  	/etc/ssl/private/ssl-cert-snakeoil.key
	#SSLCertificateChainFile	…

	<Directory /usr/local/share/letswifi-portal/htdocs>
		Require all granted
	</Directory>
	<Directory /usr/local/share/simplesamlphp/public>
		Require all granted
	</Directory>
</VirtualHost>
```
</details>


## Download and install the software

### Install SimpleSAMLphp

[Install SimpleSAMLphp](SIMPLESAMLPHP.md)

### Install Let's Wi-Fi portal

```sh
cd /usr/local/share
git clone -b beta https://github.com/geteduroam/letswifi-portal
cd letswifi-portal
composer --quiet install
cp -a config-dist /etc/letswifi
ln -s /etc/letswifi/ config

mkdir -p /var/lib/letswifi
ln -s /var/lib/letswifi var
chown www-data:www-data /var/lib/letswifi
chmod 750 /var/lib/letswifi

tee ../../bin/letswifi <<EOF
#!/usr/bin/env php
<?php
putenv( 'LETSWIFI_CONFIG_DIR=/etc/letswifi' );
require '$PWD/bin/letswifi';
EOF
chmod +x ../../bin/letswifi
```

#### Configuration

```sh
cd /etc/letswifi
chmod o-rwx *
chgrp -R www-data .
mv letswifi.conf.dist.php letswifi.conf.php
mv clients.conf.dist.php clients.conf.php
mv branding.conf.dist-eduroam.php branding.conf.php
sed -e"s@^\(\s*'dsn'\).*\$@\1 => 'sqlite:/var/lib/letswifi/letswifi.sqlite',@" \
	<database.conf.dist-sqlite.php >database.conf.php

head -c32 /dev/random | base64 | tr -d = >oauthsecret.txt
chmod 440 oauthsecret.txt
chgrp www-data oauthsecret.txt

cd /usr/local/share/letswifi-portal
mkdir -p var
sqlite3 var/letswifi.sqlite <sql/letswifi.sqlite.sql
chown -R www-data var
```

This will configure most defaults.
You might want to change **database.conf.php** if you don't want to use SQLite.

You can remove all files containing **.dist**,
these are examples and are never read by the application.

#### RADIUS certificate

You need to configure the CA certificate that you use on your RADIUS server.
While it's possible to use a self-signed CA here,
we recommend that you use a publicly trusted certificate.

Import the certificate by piping it in the import script.

<details><summary>Buypass Class 2 Root</summary>

```sh
curl -fsS https://crt.buypass.no/crt/BPClass2Rot.cer | openssl x509 -inform DER -outform PEM | letswifi ca import
```
</details>
<details><summary>Hellenic Academic and Research Institutions</summary>

```sh
curl -fsS https://www.tbs-certificats.com/issuerdata/HaricaECCRootCA2015.crt | letswifi ca import
```
</details>
<details><summary>Let's Encrypt, aka ISRG Root</summary>

```sh
curl -fsS https://letsencrypt.org/certs/isrg{rootx1,-root-x2}.pem | letswifi ca import
```
</details>

#### Realm creation

Now you can create the first realm.
In this example we create a realm for "Example Institute", domain name example.com.
Certificates issued to users will be valid for one year (365 days).
Change the command to match your setup.

Please note that newlines are not necessary in this command;
they are only provided for readability.
If you want to use newlines, please write a backslash `\` before each newline
to prevent the command from running before it's complete.

<details open><summary><strong>Realm with new signing CA and existing trust CA</strong></summary>

This option is recommended; the signing CA can be long lived and the RADIUS certificate can be one that's signed by a public CA.
In this step you'll specify which public CA is used, but the actual RADIUS server certificate does not need to be known to the portal; the client will verify it from the trusted CA you specify here.

```sh
letswifi realm example.com \
	--newca 'Example CA' \
	--lang en-GB --description 'The wireless network at the office' \
	--lang nl-NL --description 'Het draadloos netwerk op kantoor' \
	--lang en-GB --name 'Office Wi-Fi' \
	--lang nl-NL --name 'Kantoor-Wi-Fi' \
	--validity 366 \
	--trust 'C=NO, O=Buypass AS-983163327, CN=Buypass Class 2 Root CA' \
	--trust 'C=GR, L=Athens, O=Hellenic Academic and Research Institutions Cert. Authority, CN=Hellenic Academic and Research Institutions ECC RootCA 2015' \
	--trust 'C=US, O=Internet Security Research Group, CN=ISRG Root X1' \
	--trust 'C=US, O=Internet Security Research Group, CN=ISRG Root X2' \
	--server-name 'radius.example.com'
```

</details>

<details><summary><strong>Realm with existing signing CA and existing trust CA</strong></summary>

This is a slightly more advanced set-up, where you provide your own signing CA.
You may want to do this if you want to use a common root CA,
and provide an intermediate CA for client certificates.
It's recommended for larger setups.

Letswifi-portal will need the private key for the intermediate, but not for the root.

We will assume a file **bundle.pem** to contain the root certificate, intermediate certificate and intermediate key.  The subject of this intermediate is **CN=Let's Wi-Fi CA**.

```sh
letswifi ca import <bundle.pem
letswifi realm example.com \
	--lang en-GB --description 'The wireless network at the office' \
	--lang nl-NL --description 'Het draadloos netwerk op kantoor' \
	--lang en-GB --name 'Office Wi-Fi' \
	--lang nl-NL --name 'Kantoor-Wi-Fi' \
	--signer "CN=Let's Wi-Fi CA" \
	--trust 'C=GR, L=Athens, O=Hellenic Academic and Research Institutions Cert. Authority, CN=Hellenic Academic and Research Institutions ECC RootCA 2015' \
	--server-name 'radius.example.com'
```

</details>

Remove the `--trust` arguments you don't need.
If you use them, make sure you imported them in the previous section.

If you don't provide `--trust` at all, the trust will be set to the same CA that's used for signing.

#### Provider setup

Edit the **letswifi.conf.php** file.
The file contains comments to help you understand how to configure the individual settings.
Review the provider settings, especially:

* Names and descriptions
* Realms
* Authentication settings

If you want to avoid the SimpleSAMLphp *Select your identity provider* screen when logging in, set the `idpList` setting in the provider to a list with one element,
namely the EntityID of the IdP you want to use.
