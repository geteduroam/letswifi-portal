
REALM := example.com
PHP := php
SIMPLESAMLPHP_VERSION := 2.2.1
SIMPLESAMLPHP_FLAVOUR := slim

camera-ready-dev: camera-ready dev
.PHONY: camera-ready-dev

camera-ready: syntax codestyle phpunit psalm
	@# We disabled phan checking for now, due to a bug affecting Phan in this codebase
	@# https://github.com/phan/phan/issues/4887
.PHONY: camera-ready

dev: check-php config/config.conf.php config/clients.conf.php config/branding.conf.php config/database.conf.php config/realms config/certs vendor config/oauthsecret.txt
	@test -f var/letswifi-dev.sqlite || make var/letswifi-dev.sqlite
	$(PHP) -S [::1]:1080 -t htdocs/
.PHONY: dev

clean:
	rm -rf composer.phar vendor \
		phan.phar php-cs-fixer-v2.phar php-cs-fixer-v3.phar psalm.phar phpunit-7.phar \
		simplesamlphp* htdocs/simplesaml \

.PHONY: clean

config/oauthsecret.txt:
	head -c32 /dev/random | base64 | tr -d = >config/oauthsecret.txt
	chmod 440 config/oauthsecret.txt


# Code dependencies

composer.phar:
	stat composer.phar >/dev/null 2>&1 || curl -sSLO https://getcomposer.org/download/latest-stable/composer.phar || wget https://getcomposer.org/download/latest-stable/composer.phar

vendor: composer.json check-php composer.phar
	@# Some dev dependencies have very strict PHP requirements
	@# Allowing running --no-dev to work around this
	$(PHP) composer.phar --quiet --no-progress install || $(PHP) composer.phar --quiet --no-progress install --no-dev

config/clients.conf.php:
	mkdir -p config
	cp -n config-dist/clients.conf.dist.php config/clients.conf.php
config/branding.conf.php:
	mkdir -p config
	cp -n config-dist/branding-eduroam.conf.dist.php config/branding.conf.php
config/config.conf.php:
	mkdir -p config
	cp -n config-dist/config.conf.dev.php config/config.conf.php
config/database.conf.php:
	mkdir -p config
	cp -n config-dist/database.conf.dev.php config/database.conf.php
config/realms:
	mkdir -p config/realms
	cp -n config-dist/realms/example.com.conf.dist.php config/realms/example.com.conf.php
	cp -n config-dist/realms/staff.example.com.conf.dist.php config/realms/staff.example.com.conf.php
	cp -n config-dist/realms/student.example.com.conf.dist.php config/realms/student.example.com.conf.php
config/certs:
	mkdir -p config
	cp -na config-dist/certs config/
	chmod o-rwx config/certs

var:
	mkdir -p var


# Getting the software up and running quickly

var/letswifi-dev.sqlite: var
	rm -f var/letswifi-dev.sqlite
	sqlite3 var/letswifi-dev.sqlite <sql/letswifi.sqlite.sql
	## TODO: Automatically add realm and signing CA

simplesamlphp:
	-cp -n config-dist/config.conf.dist.php config/config.conf.php
	curl -sSL https://github.com/simplesamlphp/simplesamlphp/releases/download/v$(SIMPLESAMLPHP_VERSION)/simplesamlphp-$(SIMPLESAMLPHP_VERSION)-$(SIMPLESAMLPHP_FLAVOUR).tar.gz | tar xzf -
	ln -sf simplesamlphp-$(SIMPLESAMLPHP_VERSION)/ simplesamlphp || true
	ln -sf ../simplesamlphp/public/ htdocs/simplesaml || true


# Code formatters, static code sniffers etc.

check-php:
	@$(PHP) -r 'exit(json_decode("true") === true ? 0 : 1);'
.PHONY: check-php

psalm: vendor
	$(PHP) vendor/bin/psalm --no-cache
.PHONY: psalm

phan: vendor
	$(PHP) vendor/bin/phan --allow-polyfill-parser --no-progress-bar
.PHONY: phan

codestyle: vendor
	$(PHP) vendor/bin/php-cs-fixer fix
	$(PHP) vendor/bin/twig-cs-fixer fix
.PHONY: codestyle

phpunit: vendor
	$(PHP) vendor/bin/phpunit
.PHONY: phpunit

syntax: check-php
	find . ! -path './vendor/*' ! -path './simplesaml*' ! -path './lib/*' ! -path './var/*' -type f -name \*.php -print0 | xargs -0 -n1 -P50 $(PHP) -l
.PHONY: syntax
