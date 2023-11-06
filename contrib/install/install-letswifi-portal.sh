#!/bin/sh
set -e

LETSWIFI_PATH=/usr/share/letswifi-portal
#LETSWIFI_REPO=https://git.sr.ht/~eduroam/letswifi-portal
LETSWIFI_REPO=https://github.com/geteduroam/letswifi-portal.git
SIMPLESAMLPHP_PATH=/usr/share/simplesamlphp
SETTINGS_PATH=/var/lib/letswifi/answers.sh

test -f /var/lib/letswifi/answers.sh && . /var/lib/letswifi/answers.sh
fqdn="${fqdn:-$(hostname -f)}"

if test -d $LETSWIFI_PATH
then
	dialog --backtitle "Let's Wi-Fi installation" --yesno "Let's Wi-Fi appears to be installed already, in the following directory:\n\n$LETSWIFI_PATH\n\nThis script will not touch the existing installation.\n\nHowever, you may continue running this script,\nso it can refresh Apache and SimpleSAMLphp settings.\nDo you want to do that instead?" 0 0 >&2
	skip_letswifi_install=1
else
	if command -v dialog >/dev/null
	then
		dialog --backtitle "Let's Wi-Fi installation" --yesno 'This script will install the letswifi server software.\n\nContinue?' 0 0 >&2
	elif command -v whiptail >/dev/null
	then
		whiptail --yesno 'This script will install the letswifi server software.\n\nContinue?' 10 60 >&2
	else
		printf 'This script will install the letswifi server software.\n\nContinue [y/N]? ' >&2
		read answer
		printf %s "$answer" | grep -q -e Y -e y
	fi
fi

command -v dialog >/dev/null || apt-get install -y dialog || apt-get update && apt-get install -y dialog
mkdir -p /var/lib/letswifi

fqdn="$(dialog --backtitle "Let's Wi-Fi installation" --title 'Hostname' --inputbox 'Domain name for the webserver' 0 80 "$fqdn" 3>&1 1>&2 2>&3 3>&-)"
printf 'fqdn=%s\n' "$fqdn" >>/var/lib/letswifi/answers.sh
if [ "${skip_letswifi_install:-0}" = "0" ]
then
	default_realm="$(dialog --backtitle "Let's Wi-Fi installation" --title 'Default realm selection' --inputbox 'Enter the domain name that is used for the realm' 0 80 "$default_realm" 3>&1 1>&2 2>&3 3>&-)"
	printf 'default_realm=%s\n' "$default_realm" >>/var/lib/letswifi/answers.sh
fi
metadata_url="$(dialog --backtitle "Let's Wi-Fi installation" --title 'SimpleSAMLphp configuration' --inputbox 'Enter URL to SAML-IdP metadata' 0 80 "$metadata_url" 3>&1 1>&2 2>&3 3>&-)"
printf 'metadata_url=%s\n' "$metadata_url" >>/var/lib/letswifi/answers.sh

apt-get install -y ca-certificates git php-fpm php-dom php-sqlite3 php-curl sqlite3 simplesamlphp apache2 composer
a2enconf simplesamlphp "$(basename /etc/apache2/conf-available/php*-fpm.conf)"
a2dismod status

wget --output-document /var/lib/letswifi/saml-idp.xml "$metadata_url"

php << EOF > /etc/simplesamlphp/metadata/saml20-idp-remote.php
<?php
# Copying SimpleSAMLphp's code from www/admin/metadata-converter.php
# There doesn't seem to be a standard way to do this automated
require '/usr/share/simplesamlphp/vendor/autoload.php';
\$xmldata = file_get_contents('/var/lib/letswifi/saml-idp.xml');
if (empty(\$xmldata)) exit(1);
\\SimpleSAML\\Utils\\XML::checkSAMLMessage(\$xmldata, 'saml-meta');
\$entities = \\SimpleSAML\\Metadata\\SAMLParser::parseDescriptorsString(\$xmldata);
foreach (\$entities as &\$entity) {
	\$entity = [
		'shib13-sp-remote'  => \$entity->getMetadata1xSP(),
		'shib13-idp-remote' => \$entity->getMetadata1xIdP(),
		'saml20-sp-remote'  => \$entity->getMetadata20SP(),
		'saml20-idp-remote' => \$entity->getMetadata20IdP(),
	];
}
\$output = \\SimpleSAML\\Utils\\Arrays::transpose(\$entities);
echo "<?php\\n";
foreach (\$output as \$type => &\$entities) {
	foreach (\$entities as \$entityId => \$entityMetadata) {
		if (\$entityMetadata === null) {
			continue;
		}
		unset(\$entityMetadata['entityDescriptor']);
		echo '\$metadata[' . var_export(\$entityId, true) . '] = ' .
			var_export(\$entityMetadata, true) . ";\\n";
	}
}
EOF

if [ "${skip_letswifi_install:-0}" = "0" ]
then
	git clone "$LETSWIFI_REPO" "$LETSWIFI_PATH"
	( cd "$LETSWIFI_PATH"; COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev; )
	mkdir -p /var/lib/letswifi/database
	chmod 700 /var/lib/letswifi/database
	sqlite3 /var/lib/letswifi/database/letswifi.sqlite <"$LETSWIFI_PATH/sql/letswifi.sqlite.sql"
	chown -Rh www-data /var/lib/letswifi/database

	tee "$LETSWIFI_PATH/etc/letswifi.conf.php" << EOF >/dev/null
<?php return [
	'auth.service' => 'SimpleSAMLAuth',
	'auth.params' => [
		'autoloadInclude' => '$SIMPLESAMLPHP_PATH/lib/_autoload.php',
		'authSource' => 'default-sp',
	],
	'realm.selector' => null, // one of null, getparam or httphost
	'realm.default' => '$default_realm', // used when realm.selector = null
	'realm.auth' => [
		'$default_realm' => [
			'userIdAttribute' => null, // attributeName, or null for NameID
		],
	],
	'pdo.dsn' => 'sqlite:/var/lib/letswifi/database/letswifi.sqlite',
	'pdo.username' => null,
	'pdo.password' => null,
	//'signing.cert' => __DIR__ . DIRECTORY_SEPARATOR . 'signing.pem',
	'oauth.clients' => (require __DIR__ . DIRECTORY_SEPARATOR . 'clients.php'),
];
EOF
	"$LETSWIFI_PATH"/bin/add-realm.php "$default_realm" 3650
fi

tee /etc/apache2/sites-available/letswifi-portal.conf << EOF >/dev/null
<VirtualHost *:443>
	ServerName	$fqdn
	DocumentRoot $LETSWIFI_PATH/www
	Alias /.well-known/acme-challenge /var/www/html/.well-known/acme-challenge
	SSLCertificateFile /var/lib/acme/certs/$fqdn/$fqdn.cer
	SSLCertificateKeyFile /var/lib/acme/certs/$fqdn/$fqdn.key
	SSLCertificateChainFile /var/lib/acme/certs/$fqdn/fullchain.cer
</VirtualHost>
EOF

mkdir -p "/var/lib/acme/certs/$fqdn"
test -f "/var/lib/acme/certs/$fqdn/$fqdn.key" || test -f "/var/lib/acme/certs/$fqdn/$fqdn.cer" \
	|| openssl req -x509 -newkey rsa:2048 \
		-keyout "/var/lib/acme/certs/$fqdn/$fqdn.key" \
		-out "/var/lib/acme/certs/$fqdn/$fqdn.cer" \
		-sha256 -days 3650 -nodes \
		-subj "/C=XX/ST=StateName/L=CityName/O=CompanyName/OU=CompanySectionName/CN=CommonNameOrHostname"
cp "/var/lib/acme/certs/$fqdn/$fqdn.cer" "/var/lib/acme/certs/$fqdn/fullchain.cer"
a2dissite 000-default default-ssl
a2ensite letswifi-portal
a2enmod ssl proxy_fcgi
service apache2 restart
:>/var/www/html/index.html

# First fix own account for ACME
#acme_email="$(dialog --backtitle "Let's Wi-Fi installation" --title 'ACME configuration' --ok-label Yes --cancel-label No --inputbox 'A self signed certificate has been created.\n\nDo you want to obtain a certificate for $fqdn using ACME now?\n\nThen enter e-mail address for registering an ACME account' 0 0 3>&1 1>&2 2>&3 3>&- || true)"
#printf 'acme_email=%s\n' "$acme_email" >>/var/lib/letswifi/answers.sh

if [ -n "$acme_email" ]
then
	wget --output-document /usr/sbin/acme.sh https://raw.githubusercontent.com/acmesh-official/acme.sh/$BRANCH/acme.sh
	chmod +x /usr/sbin/acme.sh
	mkdir -p /var/www/html/.well-known/acme-challenge
	# todo own account for ACME
	test -f /var/lib/acme/.acme.sh/ca/api.buypass.com/acme/directory/account.json \
		|| /usr/sbin/acme.sh --server https://api.buypass.com/acme/directory --register-account --accountemail "$acme_email"
	/usr/sbin/acme.sh --server https://api.buypass.com/acme/directory --issue -d "$fqdn" --webroot /var/www/html
	# todo ACME cron
fi

dialog --backtitle "Let's Wi-Fi installation" --msgbox "Installation completed, Let's Wi-Fi should now be set up on port 443\n\nThe SAML SP metadata should be available on the following url:\n\nhttps://${fqdn}/simplesamlphp/module.php/saml/sp/metadata.php/default-sp" 0 0 >&2
