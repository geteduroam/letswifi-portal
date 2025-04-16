<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

return [
	'nl-NL' => 'Nederlands',

	// Pages showing apps and profiles for different platforms
	'If you cannot use the official app, you can download an installation profile for manual installation.' => 'Handmatig installatieprofiel indien de app niet gebruikt kan worden.',
	'There is no app available for %s.' => 'Er is geen app beschikbaar voor %s.',
	'Download an installation profile for manual installation.' => 'Download een installatieprofile voor handmatige installatie.',
	'Other options' => 'Andere opties',
	'Options for professional users' => 'Opties voor professionele gebruikers',
	'Options for other platforms and professional users' => 'Opties voor andere platformen en professionele gebruikers',
	'Generate a certificate for manual use' => 'Maak een certificaat voor handmatige installatie',

	// base.twig
	'Logged in' => 'Ingelogd',
	'User' => 'Gebruiker',
	'Login' => 'Inloggen',
	'Logout' => 'Uitloggen',
	'User information' => 'Gebruikersinformatie',

	// start.twig
	'Welcome to %1$s at %2$s' => 'Welkom bij %1$s voor %2$s',
	'To use %1$s at %2$s, download the app or profile for your device below.' => 'Om %1$s bij %2$s te gebruiken, download de app of het profiel voor jouw apparaat hieronder.',
	'Download the %s app to configure your device.' => 'Download de %s app om je apparaat in te stellen.',
	'View apps and profiles for all platforms' => 'Bekijk apps en profielen voor alle platformen',
	'login required' => 'inloggen vereist',

	// app.twig
	'Apps' => 'Apps',
	'All installer apps' => 'Alle installatie-apps',

	// realm-picker.twig
	'Realm' => 'Realm',

	// profile-download.twig
	'Profile download' => 'Profiel downloaden',
	'Download starting' => 'Je download begint zodirect',
	'Download not starting?' => 'Begint de download niet?',
	'Start download' => 'Start download',
	'Use passphrase when prompted:' => 'Wanneer je tijdens de installatie gevraagd wordt om een passphrase, gebruik deze:',

	// profile-advanced.twig
	'Download the app' => 'Download de app',
	'We recommend that you use the app' => 'Het makkelijkste is om de officiele app te gebruiken.',
	'Manual advanced profile creation' => 'Handmatig profiel maken',
	'Create configuration profile' => 'Configuratieprofiel maken',
	'Alternatively, you can use a configuration profile' => 'Voor ervaren gebruikers, en op apparaten waar geen officiele app beschikbaar is, is het ook mogelijk om een configuratieprofiel te maken.',
	'Encryption' => 'Versleuteling',
	'When encrypting you need a passphrase when installing' => 'Bij versleutelde profielen moet een passphrase ingevuld worden tijdens de installatie om het profiel te ontsleutelen.',
	'Passphrase is only needed during installation' => 'Na de installatie is de passphrase niet meer nodig; deze is enkel nodig voor de ontsleuteling tijdens de installatie.',
	'Use the feature depending encryption support on your system' => 'Gebruik deze functie in overeenstemming met of versleutelde danwel onversleutelde profielen ondersteund zijn op je systeem.',
	'Enter passphrase for encryption' => 'Voer een passphrase in om het profiel te versleutelen',
	'advanced' => 'geavanceerd',
	'optional' => 'optie',

	// error.twig
	'An error occurred' => 'Onverwachte fout opgetreden',
	'Debug info' => 'Gedetailleerde informatie (informatie tonen is ingeschakeld)',
	'Contact helpdesk' => 'Neem contact op met de helpdesk voor hulp',

	// me.twig
	'User information' => 'Gebruikersinformatie',
	'User ID' => 'Gebruikers-ID',
	'Affiliations' => 'Associaties',
	'User information is not stored after you log out.' => 'Gebruikersinformatie wordt niet opgeslagen nadat je uitlogt.',
	'User ID is connected to credentials while they are valid and short time thereafter.' => 'Je gebruikers-ID wordt wel gekoppeld aan netwerktoegangen tot enige tijd na de verloopdatum.',
	'Available realms' => 'Beschikbare realms',
	'No realms available' => 'Geen realms beschikbaar',
	'Authorised applications' => 'Goedgekeurde applicaties',
	'No authorised applications' => 'Geen goedgekeurde applicaties geregistreerd',
	'Client ID' => 'Client ID',
	'Issued' => 'Uitgegeven',
	'Expires' => 'Verloopt',
	'Revoke' => 'Intrekken',
	'Credentials' => 'Toegangen',
	'Credential' => 'Toegang',
	'No credentials' => 'Geen toegangen geregistreerd',

	// authorize.twig
	'Authorize %s' => 'Authorize %s',
	'Do you want to issue a pseudo-credential?' => 'Wil je dit apparaat toegang geven tot het Wi-Fi netwerk via je account?',
	'Approve' => 'Goedkeuren',
	'Why is this needed?' => 'Waarom is dit nodig?',
	'Requiring a manual step prevents automated enrollment.' => "Een handmatige stap voorkomt dat kwaadwillende programma's zonder jouw medeweten toegang tot het netwerk doorspelen aan anderen.",
	'Select your user realm' => 'Kies je realm om verder te gaan',
	'Continue' => 'Ga door',

	'apple-mobileconfig instructions' => 'Na het openen van het bestand op MacOS, open <strong>Systeemvoorkeuren</strong>, klik op <strong>Profiel gedownload</strong> en dubbelklik vervolgens op het nieuwe profiel.',
	'google-onc instructions' => 'Nadat het bestand gedownload is, open de Chrome browser en ga naar deze URL: <a href="chrome://network">chrome://network</a>. Gebruik daarna de <strong lang="en-US">Import ONC file</strong> knop. Er komt geen bevestiging van de import. De netwerkgegevens worden toegevoegd aan de voorkeursnetwerken.',
];
