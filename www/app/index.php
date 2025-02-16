<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

use letswifi\LetsWifiApp;
use letswifi\configuration\DictionaryFile;

require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 2 ), 'src', '_autoload.php'] );
$basePath = '..';

$app = new LetsWifiApp( basePath: $basePath );
$app->registerExceptionHandler();

// Temporary read file directly, add facility in Provider for this later
$installProfiles = new DictionaryFile( \dirname( __DIR__, 2 ) . \DIRECTORY_SEPARATOR . 'etc' . \DIRECTORY_SEPARATOR . 'userinstallers.conf.php' );

// TODO: Make platform class that handles this, move this code out of the view
$platforms = $installProfiles->getRawArray( 'platforms' );
$apps = $installProfiles->getRawArray( 'apps' );
$profiles = $installProfiles->getRawArray( 'profiles' );

foreach ( $platforms as $key => &$platform ) {
	// Set "apps" and "profiles" for the platform to the actual apps and profiles,
	// instead of just references.
	$platform['apps'] = \array_combine(
		$platform['apps'] ?? [],
		\array_map( static fn ( string $appName ): array => $apps[$appName], $platform['apps'] ?? [] ),
	);

	$platform['profiles'] = \array_combine(
		$platform['profiles'] ?? [],
		\array_map(
			static fn ( string $profileName ): array => $profiles[$profileName] + ['href' => "{$basePath}/profiles/new/{$profileName}/"],
			$platform['profiles'] ?? [],
		),
	);
}

$app->render( [
	'platforms' => $platforms,
	'advanced_href' => "{$basePath}/profiles/new/",
], 'app', $basePath );
