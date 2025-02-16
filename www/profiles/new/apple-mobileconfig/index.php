<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 4 ), 'src', '_autoload.php'] );
$basePath = '../../..';
\assert( \array_key_exists( 'REQUEST_METHOD', $_SERVER ) );

$downloadFormat = 'apple-mobileconfig';

require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__ ), '_download.php'] );
