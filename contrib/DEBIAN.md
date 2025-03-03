# Installation on Debian with MySQL

## Install packages

	apt-get install \
		cron \
		ca-certificates \
		git \
		php-fpm php-dom php-sqlite3 php-curl \
		apache2 a2enconf \
		composer \
		default-mysql-server \

## Enable PHP

a2enconf "$(basename /etc/apache2/conf-available/php*-fpm.conf)"

## Create database

	mysql
	CREATE DATABASE `letswifi` DEFAULT CHARACTER SET = `utf8mb4` DEFAULT COLLATE = `utf8mb4_unicode_ci`;


## Install the application

	mkdir -p /opt/geteduroam
	cd /opt/geteduroam
	git clone --recurse-submodules https://github.com/geteduroam/letswifi-ca.git
	cd letswifi-ca
	sed \
		-e "/autoloadInclude/ s@dirname.*$@@'/usr/share/simplesamlphp/vendor/autoload.php',@" \
		-e "/pdo\.dsn/ s@sqlite:.*',@mysql:host=localhost;dbname=letswifi@" \
		\
		<etc/letswifi.conf.simplesaml.php >etc/letswifi.conf.php


And import the SQL file with the schema

	mysql letswifi <sql/letswifi.mysql.sql


Use the [lighttpd.debian.conf] file to configure lighttpd

	cp -i lighttpd.debian.conf /etc/lighttpd/lighttpd.conf
	systemctl enable lighttpd.service
	systemctl start lighttpd.service
