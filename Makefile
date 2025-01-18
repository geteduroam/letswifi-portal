
REALM := example.com
PHP := php
SIMPLESAMLPHP_VERSION := 2.2.1
SIMPLESAMLPHP_FLAVOUR := slim

camera-ready-dev: camera-ready dev
.PHONY: camera-ready-dev

camera-ready: syntax codestyle phpunit psalm
	# We disabled phan checking for now, due to a bug affecting Phan in this codebase
	# https://github.com/phan/phan/issues/4887
.PHONY: camera-ready

dev: check-php etc/tenant.conf.php vendor etc/oauthsecret.txt
	-cp etc/tenant.conf.dev.php etc/tenant.conf.php
	@test -f var/letswifi-dev.sqlite || make var/letswifi-dev.sqlite
	$(PHP) -S [::1]:1080 -t www/
.PHONY: dev

clean:
	rm -rf composer.phar vendor \
		phan.phar php-cs-fixer-v2.phar php-cs-fixer-v3.phar psalm.phar phpunit-7.phar \
		simplesamlphp* www/simplesaml \

.PHONY: clean

etc/oauthsecret.txt:
	head -c16 /dev/random | base64 | tr -d = >etc/oauthsecret.txt
	chmod 440 etc/oauthsecret.txt


# Code dependencies

composer.phar:
	stat composer.phar >/dev/null 2>&1 || curl -sSLO https://getcomposer.org/composer.phar || wget https://getcomposer.org/composer.phar

vendor: composer.json check-php composer.phar
	# Some dev dependencies have very strict PHP requirements
	# Allowing running --no-dev to work around this
	$(PHP) composer.phar install || $(PHP) composer.phar install --no-dev

etc/tenant.conf.php:
	cp etc/tenant.conf.dist.php etc/tenant.conf.php

var:
	mkdir -p var


# Getting the software up and running quickly

var/letswifi-dev.sqlite: var
	rm -f var/letswifi-dev.sqlite
	sqlite3 var/letswifi-dev.sqlite <sql/letswifi.sqlite.sql
	$(PHP) bin/add-realm.php $(REALM) 1 || { rm var/letswifi-dev.sqlite && false; }

simplesamlphp:
	-cp -n etc/tenant.conf.dist.php etc/tenant.conf.php
	curl -sSL https://github.com/simplesamlphp/simplesamlphp/releases/download/v$(SIMPLESAMLPHP_VERSION)/simplesamlphp-$(SIMPLESAMLPHP_VERSION)-$(SIMPLESAMLPHP_FLAVOUR).tar.gz | tar xzf -
	ln -sf simplesamlphp-$(SIMPLESAMLPHP_VERSION)/ simplesamlphp || true
	ln -sf ../simplesamlphp/public/ www/simplesaml || true


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
