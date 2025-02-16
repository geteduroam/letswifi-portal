<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

return [
	'en-GB' => 'English',

	'Realm' => 'Realm',
	'If you cannot use the official app, you can download an installation profile for manual installation.' => 'If you cannot use the official app, you may also download an installation profile for manual installation.',
	'Download an installation profile for manual installation.' => 'Download an installation profile for manual installation.',
	'Other options' => 'Other options',

	'All installer apps' => 'All installer apps',
	'Generate a certificate for manual use' => 'Generate a certificate for manual use',
	'Download the %s app to configure your device.' => 'Download the %s app to configure your device.',
	'View apps and profiles for all platforms' => 'View apps and profiles for all platforms',
	'Options for other platforms and professional users' => 'Opties voor andere platformen en professionele gebruikers',
	'Options for professional users' => 'Options for professional users',

	'Profile download' => 'Profile download',
	'Download starting' => 'Your download will begin shortly',
	'Download not starting?' => 'Download not starting?',
	'Start download' => 'Start download',
	'Use passphrase when prompted:' => 'When prompted for a passphrase during installation, enter the following passphrase:',

	'advanced' => 'advanced',
	'optional' => 'optional',

	'Download the app' => 'Download the app',
	'We recommend that you use the app' => 'For most users, the easiest is to use one of the official apps.',
	'Create configuration profile' => 'Create configuration profile',
	'Alternatively, you can use a configuration profile' => 'For advanced users, and for using eduroam on a device where no app is yet available, it is also possible to download a configuration profile.',
	'Encryption' => 'Encryption',
	'When encrypting you need a passphrase when installing' => 'Encrypting your profile requires you to enter the passphrase to decrypt the contents of the profile.',
	'Passphrase is only needed during installation' => 'After installing the profile, the passphrase is not needed anymore; it is only used during installation in order to decrypt the profile contents.',
	'Use the feature depending encryption support on your system' => 'Use this option in regard whether your system supports encrypted or unencrypted profiles.',
	'Enter passphrase for encryption' => 'Enter a passphrase to encrypt the profile',

	'An error occurred' => 'An error occurred',
	'Debug info' => 'Detailed error report (due to debug enabled)',
	'Contact helpdesk' => 'Contact your helpdesk to get help',

	'Not %s?' => 'Not %s?',

	'Do you want to issue a pseudo-credential?' => 'Do you want to use your account to connect this device to the Wi-Fi network?',
	'Approve' => 'Approve',
	'Why is this needed?' => 'Why is this needed?',
	'Requiring a manual step prevents automated enrollment.' => 'By clicking approve, you allow the application to receive Wi-Fi profiles on your behalf.',
	'Select your user realm' => 'Please select your user group to continue',
	'Continue' => 'Continue',

	'apple-mobileconfig instructions' => 'After opening the file on MacOS, install it by going to <strong>System Settings</strong> → <strong>Privacy & Security</strong> → <strong>Profiles</strong>.',
	'google-onc instructions' => 'After downloading the file, open the Chrome browser and browse to this URL: <a href="chrome://network">chrome://network</a>. Then, use the <strong>Import ONC file</strong> button. The import is silent; the new network definitions will be added to the preferred networks.',
];
