
REALM := example.com

camera-ready-dev: camera-ready dev
.PHONY: camera-ready-dev

camera-ready: syntax codestyle phpunit psalm phan
.PHONY: camera-ready

dev: check-php etc/letswifi.conf.php submodule
	@test -f var/letswifi-dev.sqlite || make var/letswifi-dev.sqlite
	php -S [::1]:1080 -t www/
.PHONY: dev

clean:
	rm -rf composer.phar etc/letswifi.conf.php phan.phar php-cs-fixer-v2.phar php-cs-fixer-v3.phar psalm.phar phpunit-7.phar simplesamlphp* vendor www/simplesaml
	git submodule deinit --all
.PHONY: clean

test: syntax phpunit
.PHONY: test

######################
### Code dependencies

etc/letswifi.conf.php:
	cp etc/letswifi.conf.dist.php etc/letswifi.conf.php

var:
	mkdir -p var

var/letswifi-dev.sqlite: var submodule
	rm -f var/letswifi-dev.sqlite
	sqlite3 var/letswifi-dev.sqlite <sql/letswifi.sqlite.sql
	php bin/add-realm.php $(REALM) 1 || { rm var/letswifi-dev.sqlite && false; }

submodule:
	git submodule init
	git submodule update
.PHONY: submodule

simplesamlphp:
	cp -n etc/letswifi.conf.simplesaml.php etc/letswifi.conf.php
	curl -sSL https://github.com/simplesamlphp/simplesamlphp/releases/download/v1.18.8/simplesamlphp-1.18.8.tar.gz | tar xz
	ln -s ../simplesamlphp/www/ www/simplesaml || true
	ln -s simplesamlphp-1.18.8/ simplesamlphp || true

###############################################
### Code formatters, static code sniffers etc.
check-php:
	@php -r 'exit(json_decode("true") === true ? 0 : 1);'
.PHONY: check-php

composer.phar: check-php
	curl -sSLO https://getcomposer.org/composer.phar || wget https://getcomposer.org/composer.phar

vendor: composer.phar
	php composer.phar install

php-cs-fixer-v3.phar: check-php
	curl -sSLO https://cs.symfony.com/download/php-cs-fixer-v3.phar || wget https://cs.symfony.com/download/php-cs-fixer-v3.phar

psalm.phar: check-php
	curl -sSLO https://github.com/vimeo/psalm/releases/download/4.11.2/psalm.phar || wget https://github.com/vimeo/psalm/releases/download/4.11.2/psalm.phar

phpunit-7.phar: check-php
	curl -sSLO https://phar.phpunit.de/phpunit-7.phar || wget https://phar.phpunit.de/phpunit-7.phar

phan.phar: check-php
	curl -sSLO https://github.com/phan/phan/releases/download/5.2.1/phan.phar || wget https://github.com/phan/phan/releases/download/5.2.1/phan.phar

psalm: submodule psalm.phar
	mkdir -p vendor
	ln -s ../src/_autoload.php vendor/autoload.php || true
	php psalm.phar
.PHONY: psalm

phan: submodule phan.phar
	php phan.phar --allow-polyfill-parser --no-progress-bar
.PHONY: phan

codestyle: php-cs-fixer-v3.phar
	php php-cs-fixer-v3.phar fix
.PHONY: codestyle

phpunit: submodule phpunit-7.phar
	php phpunit-7.phar
.PHONY: phpunit

syntax: check-php
	find . ! -path './vendor/*' ! -path './simplesaml*' ! -path './lib/*' -type f -name \*.php -print0 | xargs -0 -n1 -P50 php -l
.PHONY: syntax
