#!/bin/sh
set -e

APPLICATION_DIR=/usr/share/letswifi-portal
#LETSWIFI_REPO=https://git.sr.ht/~eduroam/letswifi-portal
LETSWIFI_REPO=https://github.com/geteduroam/letswifi-portal.git
SIMPLESAMLPHP_PATH=/usr/share/simplesamlphp

WORKING_DIR=/var/lib/letswifi

SETTINGS_DIR=/etc/letswifi
SETTINGS_FILE="$SETTINGS_DIR/install-answers.sh"

fqdn="${fqdn:-$(hostname -f)}"
acme_server=https://api.buypass.com/acme/directory
test -f "$SETTINGS_FILE" && . "$SETTINGS_FILE"

if ! command -v dialog >/dev/null
then
	whiptail --yesno 'This script will install the letswifi server software.\n\nContinue?' 10 60 >&2
	apt-get -qq install dialog || apt-get -qq update && apt-get -qq install dialog
fi
command -v dialog >/dev/null || apt-get install -y dialog || { apt-get update && apt-get install -y dialog; }

mkdir -p "$WORKING_DIR"
mkdir -p "$SETTINGS_DIR"

while [ -z "$firstrun" ] || [ -z "$fqdn" ] || [ -z "$realm" ]
do
	result=$(dialog --backtitle "Let's Wi-Fi installation" --title 'Installation parameters' --ok-label 'Install'	\
		--form "Please enter your settings for this Let's Wi-Fi installation." 19 80 12	\
		'Hostname for webserver (*)        https://' 1 0 "$fqdn" 1 43 80 0	\
		'Realm for RADIUS credential (*)   @' 2 0 "$realm" 2 36 80 0	\
		'SAML federation metadata URL' 4 0 "$metadata_url" 4 35 80 0	\
		\
		'ACME server URL' 6 0 "$acme_server" 6 35 80 0	\
		'ACME registration e-mail address' 7 0 "$acme_email" 7 35 80 0	\
		\
		'Keep ACME fields empty to use a self-signed certificate instead.' 9 0 '' 0 0 0 0	\
		'Answers are written to /etc/letswifi/install-answers.sh,' 11 0 '' 0 0 0 0	\
		'it can be used to run this script with --unattend.' 12 0 '' 0 0 0 0	\
		3>&1 1>&2 2>&3 3>&-)
	printf '%s' "$result" | {
		set +e
		IFS=
		read fqdn
		read realm
		read metadata_url
		read acme_server
		read acme_email
		set -e
		printf 'fqdn=%s\nrealm=%s\nmetadata_url=%s\nacme_server=%s\nacme_email=%s\n' "$fqdn" "$realm" "$metadata_url" "$acme_server" "$acme_email" >"$SETTINGS_FILE.new"
		if [ -f "$SETTINGS_FILE" ]
		then
			diff -q "$SETTINGS_FILE.new" "$SETTINGS_FILE" && rm -f "$SETTINGS_FILE.new" || { mv "$SETTINGS_FILE" "$SETTINGS_FILE.old"; mv "$SETTINGS_FILE.new" "$SETTINGS_FILE"; }
		else
			mv "$SETTINGS_FILE.new" "$SETTINGS_FILE"
		fi
	}
	dialog --clear
	test -f "$SETTINGS_FILE" && . "$SETTINGS_FILE"
	firstrun=1
done

if [ -n "$metadata_url" ]
then
	ssp_authsource=default-sp
	user_id_attribute=null
	extra_info="The SAML SP metadata should be available on the following url:\n\nhttps://${fqdn}/simplesamlphp/module.php/saml/sp/metadata.php/default-sp"
else
	password=$(base64 /dev/random | tr -d /+ | head -c8)
	ssp_authsource=authcrypt-hash
	user_id_attribute=\'uid\'
	extra_info="You can log in to the portal with the following credentials:\nAddress:  https://${fqdn}/\nUsername: letswifi\nPassword: $password"
fi

printf '\033[0;44;37m\033[2J' >&2

apt-get install -qq cron ca-certificates git php-fpm php-dom php-sqlite3 php-curl sqlite3 simplesamlphp apache2 composer
a2enconf simplesamlphp "$(basename /etc/apache2/conf-available/php*-fpm.conf)"
a2dismod status


if [ -n "$metadata_url" ]
then
	fgrep -q "'metarefresh' => " /etc/simplesamlphp/config.php ||	\
		sed -i -e "/^ *'module\\.enable' =>/a\\"	\
			-e "         'metarefresh' => true,\\"	\
			/etc/simplesamlphp/config.php
	fgrep -q "'cron' => true" /etc/simplesamlphp/config.php ||	\
		sed -i -e "/^ *'module\\.enable' =>/a\\"	\
			-e "         'cron' => true,\\"	\
			/etc/simplesamlphp/config.php
	crontab -u www-data -l || crontab -u www-data - <<EOF
51 4 * * * php /usr/share/simplesamlphp/modules/cron/bin/cron.php -t hourly
EOF
else
	# SimpleSAMLphp changes the output of pwgen.php over the versions,
	# making it really hard for us to use it in a script
	password_hash="$(printf %s "$password" | /usr/share/simplesamlphp/bin/pwgen.php | tr -d \\n | sed -e s/hash:// -e's/^.* //')"
	fgrep -q "'authcrypt' => " /etc/simplesamlphp/config.php ||	\
		sed -i -e "/^ *'module\\.enable' =>/a\\"	\
			-e "         'authcrypt' => true,\\"	\
			/etc/simplesamlphp/config.php
	fgrep -q "\$config['authcrypt-hash']" /etc/simplesamlphp/authsources.php ||	\
		tee >>/etc/simplesamlphp/authsources.php <<EOF
\$config['authcrypt-hash'] = ['authcrypt:Hash'] + require "$SETTINGS_DIR/localusers.php";
EOF
	[ -f "$SETTINGS_DIR/localusers.php" ] && mv "$SETTINGS_DIR/localusers.php" "$SETTINGS_DIR/localusers.php.bak"
	tee >>"$SETTINGS_DIR/localusers.php" <<EOF
<?php return [
'letswifi:$password_hash' => ['uid' => 'letswifi', 'eduPersonAffiliation' => ['member', 'employee', 'staff']],
// Passwords can be hashed using /usr/share/simplesamlphp/bin/pwgen.php
];
EOF
	ssp_authsource=authcrypt-hash
	user_id_attribute=\'uid\'
	extra_info="You can log in to the portal with the following credentials:\nAddress:  https://${fqdn}/\nUsername: letswifi\nPassword: $password"
fi

if [ "${skip_letswifi_install:-0}" = "0" ]
then
	git clone "$LETSWIFI_REPO" "$APPLICATION_DIR"
	( cd "$APPLICATION_DIR"; COMPOSER_ALLOW_SUPERUSER=1 composer --no-ansi install --no-dev; )
	mkdir -p /var/lib/letswifi/database
	chmod 700 /var/lib/letswifi/database
	sqlite3 /var/lib/letswifi/database/letswifi.sqlite <"$APPLICATION_DIR/sql/letswifi.sqlite.sql"
	chown -Rh www-data /var/lib/letswifi/database

	tee "$APPLICATION_DIR/etc/letswifi.conf.php" << EOF >/dev/null
<?php
return require '$SETTINGS_DIR/letswifi.conf.php';
EOF

	tee "$SETTINGS_DIR/letswifi.conf.php" << EOF >/dev/null
<?php return [
	'auth.service' => 'SimpleSAMLAuth',
	'auth.params' => [
		'autoloadInclude' => '$SIMPLESAMLPHP_PATH/lib/_autoload.php',
		'authSource' => '$ssp_authsource',
	],
	'realm.selector' => null, // one of null or httphost
	'realm.default' => '$realm', // used when realm.selector = null
	'realm.auth' => [
		'$realm' => [
			'userIdAttribute' => $user_id_attribute, // attributeName, or null for NameID
		],
	],
	'pdo.dsn' => 'sqlite:/var/lib/letswifi/database/letswifi.sqlite',
	'pdo.username' => null,
	'pdo.password' => null,
	//'signing.cert' => __DIR__ . DIRECTORY_SEPARATOR . 'signing.pem',
	'oauth.clients' => (require '$APPLICATION_DIR/etc/clients.php'),
];
EOF
	"$APPLICATION_DIR"/bin/add-realm.php "$realm" 3650
else
	( cd "$APPLICATION_DIR"; test "$(git branch)" = '* main' && git pull; )
fi

tee /etc/apache2/sites-available/letswifi-portal.conf << EOF >/dev/null
<VirtualHost *:443>
	ServerName	$fqdn
	DocumentRoot $APPLICATION_DIR/www
	Alias /.well-known/acme-challenge /var/www/html/.well-known/acme-challenge
	SSLCertificateFile /var/lib/acme/certs/$fqdn/$fqdn.cer
	SSLCertificateKeyFile /var/lib/acme/certs/$fqdn/$fqdn.key
	SSLCertificateChainFile /var/lib/acme/certs/$fqdn/fullchain.cer
</VirtualHost>
EOF

a2dissite 000-default default-ssl
a2ensite letswifi-portal
a2enmod ssl proxy_fcgi setenvif
>/var/www/html/index.html

if [ -n "$acme_email" -a -n "$acme_server" ]
then
	acme_hostname="$(printf %s "$acme_server" | sed -es_^https://__ -es_/.*\$__)"
	[ -f /usr/bin/acme.sh ] || wget --output-document /usr/bin/acme.sh https://raw.githubusercontent.com/acmesh-official/acme.sh/master/acme.sh
	chmod +x /usr/bin/acme.sh
	mkdir -p /var/www/html/.well-known/acme-challenge
	getent passwd acme >/dev/null || useradd	\
		--comment 'ACME protocol client'	\
		--home-dir /var/lib/acme	\
		--shell /bin/sh	\
		--system acme
	mkdir -p /var/lib/acme/certs
	touch /var/log/acme.sh.log
	[ -L /var/lib/acme/.acme.sh -a -d /var/lib/acme/.acme.sh ] || ln -s ./ /var/lib/acme/.acme.sh
	chown -Rh acme:acme /var/lib/acme /var/log/acme.sh.log /var/www/html/.well-known/acme-challenge
	test -f /var/lib/acme/account.conf || tee /var/lib/acme/account.conf <<EOF
CERT_HOME="/var/lib/acme/certs"
LOG_FILE='/var/log/acme.sh.log'
ECC_SUFFIX=
EOF
	test -d "/var/lib/acme/.acme.sh/ca/$acme_hostname"	\
		|| su acme -c "/usr/bin/acme.sh --no-color --server '$acme_server' --register-account --accountemail '$acme_email'"
	if ! service apache2 status >/dev/null
	then
		a2dissite letswifi-portal
		service apache2 start
	fi
	su acme -c "/usr/bin/acme.sh --no-color --server '$acme_server' --issue -d '$fqdn' --webroot /var/www/html"
	a2ensite letswifi-portal
	crontab -u acme -l || crontab -u acme - <<EOF
17 4 * * * /usr/bin/acme.sh/acme.sh --cron --home /var/lib/acme > /dev/null
EOF
fi
mkdir -p "/var/lib/acme/certs/$fqdn"
test -f "/var/lib/acme/certs/$fqdn/$fqdn.key" || test -f "/var/lib/acme/certs/$fqdn/$fqdn.cer"	\
	|| openssl req -x509 -newkey rsa:2048	\
		-keyout "/var/lib/acme/certs/$fqdn/$fqdn.key"	\
		-out "/var/lib/acme/certs/$fqdn/$fqdn.cer"	\
		-sha256 -days 3650 -nodes	\
		-subj "/C=XX/ST=StateName/L=CityName/O=CompanyName/OU=CompanySectionName/CN=CommonNameOrHostname"
cp -n "/var/lib/acme/certs/$fqdn/$fqdn.cer" "/var/lib/acme/certs/$fqdn/fullchain.cer" || true
service apache2 restart

printf '\033[0m' >&2
dialog --backtitle "Let's Wi-Fi installation" --msgbox "Installation completed, Let's Wi-Fi should now be set up on port 443\n\n$extra_info\n\nConfiguration files for Let's Wi-Fi and SimpleSAMLphp are respectively\nlocated in /etc/letswifi and /etc/simplesaml" 0 0 >&2
