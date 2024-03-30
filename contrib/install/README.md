# Installation script

This script will install letswifi-portal on a clean installation.
The following operating systems are currently supported:

* Debian 12
* Debian 11
* Ubuntu 22.04*

<details>
<summary>*Note: Ubuntu ships with a broken SimpleSAMLphp and requires a patch*</summary>

	sed -i -e'/ASSERT_QUIET_EVAL/d' /usr/share/simplesamlphp/lib/SimpleSAML/Error/Assertion.php

<detail>

Simply download the script and run it.

	wget https://github.com/geteduroam/letswifi-portal/raw/main/contrib/install/install-letswifi-portal.sh
	chmod +x install-letswifi-portal.sh
	./install-letswifi-portal.sh

Then follow the instructions.

The script will install packages and write data in the following locations:

* `/usr/share/letswifi-portal` (the application, no backup necessary)
* `/var/lib/letswifi` (dynamic data, backup strongly recommended)
* `/etc/letswifi` (configuration files, static after installation, backup with low frequency)

State of the web application (the database containing issued certificates)
is stored in `/var/lib/letswifi/database`.

This means that the installation can easily be migrated to another host,
by simply copying over the database.  You can also use a MySQL database,
if you anticipate heavy use and would like to run the application in a cluster.

**It is safe to run multiple instances of the application against the same database**

You can also edit configuration files in `/etc/letswifi` and `/etc/simplesamlphp` for fine-tuning.
