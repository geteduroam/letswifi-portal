<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2022, Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: 2020-2022, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 2 ), 'src', '_autoload.php'] );
$basePath = '..';

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();

$app->requireAdmin( 'admin' );

$app->render( [
	'href' => "{$basePath}/admin/",
], 'admin', $basePath );
