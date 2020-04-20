
camera-ready-dev: camera-ready dev

camera-ready: syntax codestyle phpunit psalm phan

dev: etc/geteduroam.conf.php var/geteduroam-dev.sqlite
	php -S [::1]:1080 -t www/

clean:
	rm -rf composer.phar etc/geteduroam.conf.php phan.phar php-cs-fixer-v2.phar psalm.phar phpunit-7.phar vendor

test: syntax phpunit

composer.phar:
	curl -sSLO https://getcomposer.org/composer.phar || wget https://getcomposer.org/composer.phar

php-cs-fixer-v2.phar:
	curl -sSLO https://cs.sensiolabs.org/download/php-cs-fixer-v2.phar || wget https://cs.sensiolabs.org/download/php-cs-fixer-v2.phar

psalm.phar:
	curl -sSLO https://github.com/vimeo/psalm/releases/download/3.10.1/psalm.phar || wget https://github.com/vimeo/psalm/releases/download/3.10.1/psalm.phar

phpunit-7.phar:
	curl -sSLO https://phar.phpunit.de/phpunit-7.phar || wget https://phar.phpunit.de/phpunit-7.phar

phan.phar:
	curl -sSLO https://github.com/phan/phan/releases/download/2.7.0/phan.phar || wget https://github.com/phan/phan/releases/download/2.7.0/phan.phar

#vendor: composer.phar
#	php composer.phar install

psalm: psalm.phar
	mkdir -p vendor
	ln -s ../src/_autoload.php vendor/autoload.php || true
	php psalm.phar

phan: phan.phar
	php phan.phar --allow-polyfill-parser --no-progress-bar

codestyle: php-cs-fixer-v2.phar
	php php-cs-fixer-v2.phar fix

phpunit: phpunit-7.phar
	php phpunit-7.phar

syntax:
	find . ! -path './vendor/*' -name \*.php -print0 | xargs -0 -n1 -P50 php -l

etc/geteduroam.conf.php:
	cp etc/geteduroam.conf.dist.php etc/geteduroam.conf.php

var:
	mkdir -p var

var/geteduroam-dev.sqlite: var
	sqlite3 var/geteduroam-dev.sqlite <sql/geteduroam-dev.sqlite.sql
	php bin/init-db.php || { rm var/geteduroam-dev.sqlite && false; }

.PHONY: camera-ready codestyle psalm phan phpunit phpcs clean syntax test dev
