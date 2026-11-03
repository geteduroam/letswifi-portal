<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * SPDX-License-Identifier: BSD-3-Clause
 */

return [
	'dsn' => 'sqlite:' . \dirname( __DIR__ ) . '/var/letswifi.sqlite',
	'username' => null,
	'password' => null,
];
