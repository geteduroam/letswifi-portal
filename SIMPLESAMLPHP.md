# Installation SimpleSAMLphp for production use

The
[official installation guide for SimpleSAMLphp](https://simplesamlphp.org/docs/stable/simplesamlphp-install.html)
recommends you install it in `/var`, but following
[The Filesystem Hierarchy Standard](https://www.man7.org/linux/man-pages/man7/hier.7.html),
we think you should install it in `/usr/local/share` instead.

>[!IMPORTANT]
> Debian and Ubuntu have packages for SimpleSAMLphp, but they're outdated.
> We need to use at least version 2.x the packages provided are for 1.x.
> SimpleSAMLphp 1.x **WILL NOT WORK** with this application.
> Configuration files from SimpleSAMLphp 1.x **ARE NOT COMPATIBLE** with SimpleSAMLphp 2.x.

>[!TIP]
> The guide assumes Debian/Ubuntu.
> For FreeBSD, replace `www-data` with `www` and replace `/var/lib` with `/var/db`.

## Installation

<details open><summary>Fresh installation</summary>

```sh
export SSPVER=2.3.6
cd /usr/local/share
curl -L https://github.com/simplesamlphp/simplesamlphp/releases/download/v$SSPVER/simplesamlphp-$SSPVER-full.tar.gz | tar xzvf -
mv simplesamlphp-$SSPVER simplesamlphp
mv simplesamlphp/config /etc/simplesamlphp
rm -r simplesamlphp/metadata
ln -s /etc/simplesamlphp simplesamlphp/config
cp -n /etc/simplesamlphp/config.php.dist /etc/simplesamlphp/config.php
mkdir -p /var/lib/simplesamlphp /var/cache/simplesamlphp /etc/simplesamlphp/metadata
chown www-data /var/lib/simplesamlphp /var/cache/simplesamlphp /etc/simplesamlphp/metadata
```
</details>
<details><summary>Upgrade</summary>

```sh
export SSPVER=2.3.6
cd /usr/local/share
curl -L https://github.com/simplesamlphp/simplesamlphp/releases/download/v$SSPVER/simplesamlphp-$SSPVER-full.tar.gz | tar xzvf -
mv simplesamlphp simplesamlphp-old
mv simplesamlphp-$SSPVER simplesamlphp
rm -r simplesamlphp/config simplesamlphp/metadata
ln -s /etc/simplesamlphp simplesamlphp/config
rm -r simplesamlphp-old
```
</details>

## Web Server

Make sure that you can reach the application in your webserver from the `/simplesaml` endpoint.
For Apache, you can add this snippet to your VirtualHost:

```html
<VirtualHost *>
	# ...

	Alias /simplesaml /usr/local/share/simplesamlphp/public

	<Directory /usr/local/share/simplesamlphp/public>
		Require all granted
	</Directory>
</VirtualHost>
```

>[!TIP]
> We already added these lines to the example vhost in the installation guide for letswifi-portal,
> so you don't have to add them if you use that example.

## /etc/simplesamlphp/config.php

If the file doesn't exist, copy from **config.php.dist**.

Edit `/etc/simplesamlphp/config.php`, make the following changes (for FreeBSD, use `/var/db` instead of `/var/lib`):

```diff
-	//'datadir' => '/var/data/',
-	//'tempdir' => '/tmp/simplesamlphp',
+	'datadir' => '/var/lib/simplesamlphp/data/',
+	'tempdir' => '/var/lib/simplesamlphp/tmp/',
```

Generate a secret salt with `base64 </dev/random | tr -d /+ | head -c32 ; echo`

```diff
-	'secretsalt' => 'defaultsecretsalt',
+	'secretsalt' => 'somethingdifferent',
```

```diff
-	'auth.adminpassword' => '123',
+	'auth.adminpassword' => '1234',
```

```diff
-	'metadatadir' => 'metadata',
+	'metadatadir' => 'config/metadata',
```

```diff
	'module.enable' => [
		'exampleauth' => false,
+		'metarefresh' => true,
+		'cron' => true,
		'core' => true,
		'admin' => true,
		'saml' => true
	],
```

## /etc/simplesamlphp/authsources.php

If the file doesn't exist, copy from **authsources.php.dist**.

```diff
-		'entityID' => 'https://myapp.example.org/',
+		'entityID' => 'https://' . $_SERVER['HTTP_HOST'],
```

## /etc/simplesamlphp/module_cron.php

Replace the file with the following contents

```php
<?php
$config = [
	'allowed_tags' => ['daily'],
	'debug_message' => true,
	'sendemail' => false,
];
```

## /etc/simplesamlphp/module_metarefresh.php

Replace the file to add URLs for your IdP metadata.

>[!IMPORTANT]
> Update the URL to the metadata URL for your IdP

```php
<?php
$config = ['sets' => [[
	'cron' => ['daily'],
	'sources' => [
		// Add all metadata URLs here, at least one. One per line.
		['src' => 'https://engine.test.surfconext.nl/authentication/idp/metadata'],
		// ['src' => '…other URL'],
	],

	'expireAfter' => 604800, // Maximum one week cache time (3600*24*7)
	'outputDir' => 'config/metadata/',

	'outputFormat' => 'flatfile',
]]];
```

## Cron

Run `crontab -u www-data -e` and make sure it contains a line to run the script.

```cron
# m h dom mon dow   command
  * 4 *   *   *     php /usr/local/share/simplesamlphp/modules/cron/bin/cron.php -t daily >/dev/null
```

Then run the script manually to confirm it works.

```sh
sudo -u www-data php /usr/local/share/simplesamlphp/modules/cron/bin/cron.php -t daily
```

Confirm that output contains `Cron did run tag [daily]`, and that a file `/etc/simplesamlphp/metadata/saml20-idp-remote.php` has been created.

## Metadata exchange

Navigate to **/simplesaml/module.php/admin/federation** on your webserver,
e.g. go to `https://example.com/simplesaml/module.php/admin/federation`, replacing `example.com` with your domain.

Here you will find the metadata or metadata URL to provide to your IdP.

After you've exchanged metadata, you can test the authentication by pressing **Test**, and then **default-sp**.
