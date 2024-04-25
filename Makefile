
REALM := example.com
PHP := php
SIMPLESAMLPHP_VERSION := 2.2.1
SIMPLESAMLPHP_FLAVOUR := slim

camera-ready-dev: camera-ready dev
.PHONY: camera-ready-dev

camera-ready: syntax codestyle phpunit psalm phan
.PHONY: camera-ready

dev: check-php etc/letswifi.conf.php vendor
	@test -f var/letswifi-dev.sqlite || make var/letswifi-dev.sqlite
	$(PHP) -S [::1]:1080 -t www/
.PHONY: dev

clean:
	rm -rf composer.phar etc/letswifi.conf.php phan.phar php-cs-fixer-v2.phar php-cs-fixer-v3.phar psalm.phar phpunit-7.phar simplesamlphp* vendor www/simplesaml
.PHONY: clean

test: syntax phpunit
.PHONY: test


# Code dependencies

composer.phar:
	stat composer.phar >/dev/null 2>&1 || curl -sSLO https://getcomposer.org/composer.phar || wget https://getcomposer.org/composer.phar

vendor: composer.json check-php composer.phar
	# Some dev dependencies have very strict PHP requirements
	# Allowing running --no-dev to work around this
	$(PHP) composer.phar install || $(PHP) composer.phar install --no-dev

composer.lock: composer.json check-php composer.phar
	$(PHP) composer.phar update

etc/letswifi.conf.php:
	cp etc/letswifi.conf.dist.php etc/letswifi.conf.php

var:
	mkdir -p var


# Getting it up and running quickly

var/letswifi-dev.sqlite: var
	rm -f var/letswifi-dev.sqlite
	sqlite3 var/letswifi-dev.sqlite <sql/letswifi.sqlite.sql
	$(PHP) bin/add-realm.php $(REALM) 1 || { rm var/letswifi-dev.sqlite && false; }

simplesamlphp:
	-cp -n etc/letswifi.conf.simplesaml.php etc/letswifi.conf.php
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
.PHONY: codestyle

phpunit: vendor
	$(PHP) vendor/bin/phpunit
.PHONY: phpunit

syntax: check-php
	find . ! -path './vendor/*' ! -path './simplesaml*' ! -path './lib/*' -type f -name \*.php -print0 | xargs -0 -n1 -P50 $(PHP) -l
.PHONY: syntax
