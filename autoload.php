<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

// This file is intended to be overridden by the packager
// It is included by all php files from htdocs

require \implode( \DIRECTORY_SEPARATOR, [__DIR__, 'src', '_autoload.php'] );
