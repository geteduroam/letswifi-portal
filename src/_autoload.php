<?php
/*
 * Copyright (c) 2014-2015, Jørn Åne de Jong <@jornane>
 *
 * Permission to use, copy, modify, and/or distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */

/**
 * Register SPL autoloading for classes and interfaces.  Put this file in your
 * namespace root and make sure it gets included from your PHP entry-point.
 *
 * There is no requirement for capitalisation for your namespaces and classes,
 * but all folders and files MUST be lower-case.  Class names are automatically
 * lower-cased on autoload.  This conforms to the PHP design philosophy that
 * functions and class names must be case-insensitive.
 *
 * The include path is changed to prefer the current directory over the
 * established include path.  This is because manual include/require statements
 * will likely not be used, because it will be handled by autoloading.
 * Feel free to change this behaviour if that works better for you.
 *
 * @author Jørn Åne de Jong <@jornane>
 * @copyright Copyright (c) 2014-2015, Jørn Åne de Jong <@jornane>
 * @link https://gist.github.com/jornane/667f2e3acc262ce6bf44
 * @link http://php.net/manual/en/function.spl-autoload.php
 * @license http://choosealicense.com/licenses/isc/ ISC license
 */

\spl_autoload_extensions( '.php' );
\spl_autoload_register( 'spl_autoload' );
\set_include_path( \realpath( __DIR__ ) . \PATH_SEPARATOR . \get_include_path() );

// Load Composer autoloader
require \realpath( \dirname( __DIR__ ) ) . '/vendor/autoload.php';
