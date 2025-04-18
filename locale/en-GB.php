<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

return [
	'en-GB' => 'English',

	// Pages showing apps and profiles for different platforms
	'If you cannot use the official app, you can download an installation profile for manual installation.' => 'If you cannot use the official app, you may also download an installation profile for manual installation.',
	'There is no app available for %s.' => 'There is no app available for %s.',
	'Download an installation profile for manual installation.' => 'Download an installation profile for manual installation.',
	'Other options' => 'Other options',
	'Options for professional users' => 'Options for professional users',
	'Options for other platforms and professional users' => 'Options for other platforms and professional users',
	'Generate a certificate for manual use' => 'Generate a certificate for manual use',

	// base.twig
	'Language' => 'Language',
	'Account' => 'Account',
	'Login' => 'Login',
	'Logout' => 'Logout',
	'Account information' => 'Account information',

	// start.twig
	'Welcome to %1$s at %2$s' => 'Welcome to %1$s at %2$s',
	'To use %1$s at %2$s, download the app or profile for your device below.' => 'To use %1$s at %2$s, download the app or profile for your device below.',
	'Download the %s app to configure your device.' => 'Download the %s app to configure your device.',
	'View apps and profiles for all platforms' => 'View apps and profiles for all platforms',
	'login required' => 'login required',

	// app.twig
	'Apps' => 'Apps',
	'All installer apps' => 'All installer apps',

	// realm-picker.twig
	'Realm' => 'Realm',

	// profile-download.twig
	'Profile download' => 'Profile download',
	'Download %s profile' => 'Download %s profile',
	'Download starting' => 'Your download will begin shortly',
	'Download not starting?' => 'Download not starting?',
	'Start download' => 'Start download',
	'Use passphrase when prompted:' => 'When prompted for a passphrase during installation, enter the following passphrase:',

	// profile-advanced.twig
	'Download the app' => 'Download the app',
	'We recommend that you use the app' => 'For most users, the easiest is to use one of the official apps.',
	'Manual advanced profile creation' => 'Manual advanced profile creation',
	'Create configuration profile' => 'Create configuration profile',
	'Alternatively, you can use a configuration profile' => 'For advanced users, or on a device where no app is yet available, it is also possible to download a configuration profile.',
	'Encryption' => 'Encryption',
	'When encrypting you need a passphrase when installing' => 'Encrypting your profile requires you to enter the passphrase to decrypt the contents of the profile.',
	'Passphrase is only needed during installation' => 'After installing the profile, the passphrase is not needed anymore; it is only used during installation in order to decrypt the profile contents.',
	'Use the feature depending encryption support on your system' => 'Use this option in regard whether your system supports encrypted or unencrypted profiles.',
	'Enter passphrase for encryption' => 'Enter a passphrase to encrypt the profile',
	'advanced' => 'advanced',
	'optional' => 'optional',

	// error.twig
	'An error occurred' => 'An error occurred',
	'Debug info' => 'Detailed error report (due to debug enabled)',
	'Contact helpdesk' => 'Contact your helpdesk to get help',

	// me.twig
	'User ID' => 'User ID',
	'Affiliations' => 'Affiliations',
	'User information is not stored after you log out.' => 'User information is not stored after you log out.',
	'User ID is connected to credentials while they are valid and short time thereafter.' => 'User ID is connected to credentials while they are valid and short time thereafter.',
	'Available realms' => 'Available realms',
	'No realms available' => 'No realms available',
	'Authorised applications' => 'Authorised applications',
	'No authorised applications' => 'No authorised applications',
	'Client ID' => 'Client ID',
	'Issued' => 'Issued',
	'Expires' => 'Expires',
	'Revoke' => 'Revoke',
	'Credentials' => 'Credentials',
	'Credential' => 'Credential',
	'No credentials' => 'No credentials',

	// authorize.twig
	'Authorize %s' => 'Authorize %s',
	'Do you want to issue a pseudo-credential?' => 'Do you want to use your account to connect this device to the Wi-Fi network?',
	'Approve' => 'Approve',
	'Why is this needed?' => 'Why is this needed?',
	'Requiring a manual step prevents automated enrollment.' => 'By clicking approve, you allow the application to receive Wi-Fi profiles on your behalf.',
	'Select your user realm' => 'Please select your user group to continue',
	'Continue' => 'Continue',

	'apple-mobileconfig instructions' => 'After opening the file on MacOS, install it by opening the <strong>System Settings</strong> app, click <strong>Profile Downloaded</strong> and then double-click the new profile.',
	'google-onc instructions' => 'After downloading the file, open the Chrome browser and browse to this URL: <a href="chrome://network">chrome://network</a>. Then, use the <strong>Import ONC file</strong> button. The import is silent; the new network definitions will be added to the preferred networks.',

	// filenames for localised store badges
	'Download from the Microsoft Store' => 'Download from the Microsoft Store',
	'en-us%%20%s.svg' => 'en-us%%20%s.svg',

	'Get it on F-Droid' => 'Get it on F-Droid',
	'get-it-on-en.svg' => 'get-it-on-en.svg',

	'Download on the App Store' => 'Download on the App Store',
	'Download_on_the_App_Store_Badge_US-UK_RGB_blk_092917.svg' => 'Download_on_the_App_Store_Badge_US-UK_RGB_blk_092917.svg',

	'Get it on Google Play' => 'Get it on Google Play',
	'Google_Play_Store_badge_EN.svg' => 'Google_Play_Store_badge_EN.svg',
];
