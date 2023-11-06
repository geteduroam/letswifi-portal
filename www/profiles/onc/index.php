<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2022, Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: 2020-2022, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

require \implode(\DIRECTORY_SEPARATOR, [\dirname(__DIR__, 3), 'src', '_autoload.php']);
$basePath = '../..';
\assert( \array_key_exists( 'REQUEST_METHOD', $_SERVER ) );

$downloadKind = 'google-onc';
$href = "${basePath}/profiles/onc/";

require \implode(\DIRECTORY_SEPARATOR, [\dirname(__DIR__), 'new', '_download.php']);
