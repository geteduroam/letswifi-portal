<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

return [
	'de-DE' => 'Deutsch',

	// Pages showing apps and profiles for different platforms
	'If you cannot use the official app, you can download an installation profile for manual installation.' => 'Keine kompatible App verfügbar?',
	'There is no app available for %s.' => 'Es steht keine App für %s zur Verfügung.',
	'Download an installation profile for manual installation.' => 'Ein Profil für die manuelle Konfiguration herunterladen.',
	'Other options' => 'Weitere Optionen',
	'Options for professional users' => 'Erweiterte Optionen',
	'Options for other platforms and professional users' => 'Andere Platformen und erweiterte Optionen',
	'Generate a certificate for manual use' => 'Ein Zertifikat für manuelle Konfiguration erzeugen',

	// base.twig
	'Language' => 'Sprache',
	'Account' => 'Account',
	'Login' => 'Anmelden',
	'Logout' => 'Abmelden',
	'Account information' => 'Profil',

	// start.twig
	'Welcome to %1$s at %2$s' => 'Willkommen im %1$s Portal der %2$s',
	'To use %1$s at %2$s, download the app or profile for your device below.' => 'Um mit der Einrichtung von %1$s an der %2$s zu beginnen, laden Sie die passende App für Ihr Gerät herunter:',
	'Download the %s app to configure your device.' => 'Die passende App für %s herunterladen.',
	'View apps and profiles for all platforms' => 'Alle Systeme und Optionen anzeigen',
	'login required' => 'Anmeldung erfoderlich',

	// app.twig
	'Apps' => 'Apps',
	'All installer apps' => 'Anwendungen für alle Platformen',

	// realm-picker.twig
	'Realm' => 'Realm',

	// profile-download.twig
	'Profile download' => 'Profil herunterladen',
	'Download %s profile' => 'Download %s Profil',
	'Download starting' => 'Der Download beginnt in kürze...',
	'Download not starting?' => 'Download startet nicht?',
	'Start download' => 'Download starten',
	'Use passphrase when prompted:' => 'Wird während der Installation ein Passwort verlangt, geben Sie folgendes ein:',

	// profile-advanced.twig
	'Download the app' => 'Die App herunterladen',
	'We recommend that you use the app' => 'Für eine komfortable Einrichtung empfehlen wir die Nuztung der offiziellen geteduroam-App',
	'Manual advanced profile creation' => 'Manuelle, erweiterte Profilerstellung',
	'Manual certificate creation' => 'Zertifikat manuell erzeugen',
	'Create configuration profile' => 'Konfigurationsprofil erzeugen',
	'Alternatively, you can use a configuration profile' => 'Für (noch) nicht durch die App unterstützte Systeme, oder für Experten kann alternativ auch ein Konfigurationsprofil erzeugt werden.',
	'Encryption' => 'Verschlüsselung',
	'When encrypting you need a passphrase when installing' => 'Um Ihr Konfiugrationsprofil zu verschlüsseln geben Sie im Folgenden bitte eine Passphrase ein.',
	'Passphrase is only needed during installation' => 'Die Passphrase wird nach der erfolgten Installation nicht mehr benötigt.',
	'Use the feature depending encryption support on your system' => 'Soll die eduroam CAT App verwendet werden ist eine Passphrase zwingend erforderlich. Für andere Konfigurationsmethoden & Plattformen achten Sie auf die ensprechende Kompatibilität und überspringeng ggf. diese Option.',
	'Enter passphrase for encryption' => 'Passphrase zur Verschlüsselung:',
	'advanced' => 'erweitert',
	'optional' => 'optional',

	// error.twig
	'An error occurred' => 'Ein Fehler ist aufgetreten',
	'Debug info' => 'Detaillierter Fehlerbericht (Debugging aktiv)',
	'Contact helpdesk' => 'Für Unterstützung wenden Sie sich bitte an den Support.',

	// me.twig
	'User ID' => 'Benutzername',
	'Affiliations' => 'Zugangsprofile',
	'User information is not stored after you log out.' => 'Benutzerinformationen werden nach dem Abmelden nicht gespeichert',
	'User ID is connected to credentials while they are valid and short time thereafter.' => 'Der Benutzername ist mit den Zertifikaten nur während Ihrer Laufzeit (und kurze Zeit dannach) verknüpft.',
	'Available realms' => 'Verfügbare Zugangsgruppen',
	'No realms available' => 'Keine Zugangsgruppe verfügbar',
	'Authorised applications' => 'Berechtigte Apps',
	'No authorised applications' => 'Keine derzeit berechtigte App',
	'Client ID' => 'Benutzername',
	'Issued' => 'Ausgestellt',
	'Expires' => 'Läuft ab',
	'Revoke' => 'Widerrufen',
	'Credentials' => 'Zertifikate/Profile',
	'Credential' => 'Zertifikat/Profil',
	'No credentials' => 'Kein Zertifikat/Profil',

	// authorize.twig
	'Authorize %s' => '%s Autorisieren',
	'Do you want to issue a pseudo-credential?' => 'Mit Ihren Zugangsdaten ein Profil für das WLAN-Netzwerk erzeugen und das Gerät verbinden?',
	'Approve' => 'Zustimmen',
	'Why is this needed?' => 'Warum ist das notwendig?',
	'Requiring a manual step prevents automated enrollment.' => 'Mit der Zustimmung gestatten Sie der Anwendung für Sie ein Zertifikat/Profil abzurufen. (Die Anforderung zur Zustimmung verhindert einen automatisierten Mehrfachabruf)',
	'Select your user realm' => 'Bitte wählen Sie Ihr gewünschtes Zugangsprofil',
	'Continue' => 'Fortfahren',

	'apple-mobileconfig instructions' => 'Nach dem Download muss das Profil über die <span style="font-weight:bold;">Systemeinstellungen</span> installiert werden. Folgen Sie nach dem Öffnen den Benachrichtigungen oder wechseln in den Systemeinstellungen zu <span style="font-weight:bold;">Allgemein > Geräteverwaltung > Heruntergeladene Profile</span> und starten die Installation über einen Doppelklick.',
	'google-onc instructions' => 'Nach dem Download der Profildatei folgende URL aufrufen: <a href="chrome://network">chrome://network</a>. Anschließend die Option <span style="font-weight:bold;">Import ONC file</span> wählen. Die Einrichtung ist abgeschlossen - Es erfolgt <span style="font-weight:bold;">keine</span> Bestätigung!',

	// filenames for localised store badges
	'Download from the Microsoft Store' => 'Aus dem Microsoft Store laden',
	'en-us%%20%s.svg' => 'de%%20%s.svg',

	'Get it on F-Droid' => 'Aus dem F-Droid Store laden',
	'get-it-on-en.svg' => 'get-it-on-de.svg',

	'Download on the App Store' => 'Aus dem App Store laden',
	'Download_on_the_App_Store_Badge_US-UK_RGB_blk_092917.svg' => 'Download_on_the_App_Store_Badge_DE_RGB_blk_092917.svg',

	'Get it on Google Play' => 'Aus dem Google Play Store laden',
	'Google_Play_Store_badge_EN.svg' => 'Google_Play_Store_badge_DE.svg',

	'Get it on Flathub' => 'Aus Flathub laden',
	'badge-en.svg' => 'badge-de.svg',
];
