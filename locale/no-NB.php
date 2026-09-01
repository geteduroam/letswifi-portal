<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

return [
	'no-NB' => 'norsk bokmål',

	// Pages showing apps and profiles for different platforms
	'If you cannot use the official app, you can download an installation profile for manual installation.' => 'Hvis du ikke kan bruke den ofisielle appen, kan du laste ned en WiFi-profil for manuell installasjon.',
	'There is no app available for %s.' => 'Det finnes ingen app for %s.',
	'Download an installation profile for manual installation.' => 'Last ned profil for manuell installasjon.',
	'Other options' => 'Andre valg',
	'Options for professional users' => 'Valg for profesjonelle brukere',
	'Options for other platforms and professional users' => 'Valg for profesjonelle brukere og andre plattformer',
	'Generate a certificate for manual use' => 'Generer sertifikat for manuelt bruk',

	// base.twig
	'Language' => 'Språk',
	'Account' => 'Konto',
	'Login' => 'Logg inn',
	'Logout' => 'Logg ut',
	'Account information' => 'Kontoinformasjon',

	// start.twig
	'Welcome to %1$s at %2$s' => 'Velkommen til %1$s for %2$s',
	'To use %1$s at %2$s, download the app or profile for your device below.' => 'For å bruke %1$s hos %2$s, last ned appen for din enhet under.',
	'Download the %s app to configure your device.' => 'Last ned %s appen for å konfigurere opp din enhet.',
	'View apps and profiles for all platforms' => 'Vis apper og profiler for alle plattformer',
	'login required' => 'krever pålogging',

	// app.twig
	'Apps' => 'Apper',
	'All installer apps' => 'Alle appinstallasjoner',

	// realm-picker.twig
	'Realm' => 'Realm',

	// profile-download.twig
	'Profile download' => 'Profilnedlastning',
	'Download %s profile' => 'Last ned profil %s',
	'Download starting' => 'Nedlastningen starter straks',
	'Download not starting?' => 'Starter ikke nedlastningen?',
	'Start download' => 'Start nedlastning',
	'Use passphrase when prompted:' => 'Når du blir spurt om passord under installasjon, skriv følgende passord:',

	// profile-advanced.twig
	'Download the app' => 'Last ned appen',
	'We recommend that you use the app' => 'Det enklest å bruke de ofisielle appene, for de fleste brukere.',
	'Manual certificate creation' => 'Manuell oppretting av sertifikat',
	'Manual advanced profile creation' => 'Manuell avansert oppretting av profil',
	'Create configuration profile' => 'Opprett profil',
	'Alternatively, you can use a configuration profile' => 'For avanserte brukere, eller hvis appen ikke er tilgjengelig for din enhet, er det mulig å laste ned en profil manuelt.',
	'Encryption' => 'Kryptering',
	'When encrypting you need a passphrase when installing' => 'Hvis du vil kryptere profilen din må du skrive inn passordet for å dekryptere innholdet.',
	'Passphrase is only needed during installation' => 'Når profilen er installert trenger du ikke passordet lenger; passordet brukes kun når du installerer profilen.',
	'Use the feature depending encryption support on your system' => 'Bruk av dette valget avhenger av om din enhet støtter krypterte eller ukrypterte profiler.',
	'Enter passphrase for encryption' => 'Skriv inn passord for å kryptere profilen',
	'advanced' => 'avansert',
	'optional' => 'valgfritt',

	// error.twig
	'An error occurred' => 'En feil oppstod',
	'Debug info' => 'Detaljert feilmelding (feilsøking er skrudd på)',
	'Contact helpdesk' => 'Kontakt ditt universitet, høyskole eller arbeidsgiver for hjelp',

	// me.twig
	'User ID' => 'Bruker-ID',
	'Affiliations' => 'Tilknytninger',
	'User information is not stored after you log out.' => 'Når du logger ut blir ingen informasjon om deg lagret.',
	'User ID is connected to credentials while they are valid and short time thereafter.' => 'Bruker-ID er tilknyttet legitimasjonsinformasjonen mens de er gyldige, og en kort tid etter.',
	'Available realms' => 'Tilgjengelige realms',
	'No realms available' => 'Ingen realms er tilgjengelige',
	'Authorised applications' => 'Autoriserte applikasjoner',
	'No authorised applications' => 'Ingen autoriserte applikasjoner tilgjengelig',
	'Client ID' => 'Klient-ID',
	'Issued' => 'Opprettet',
	'Expires' => 'Slutter å virke',
	'Revoke' => 'Slett',
	'Credentials' => 'Din legitimasjonsinfo',
	'Credential' => 'Brukernavn',
	'No credentials' => 'Ingen brukernavn funnet for deg',

	// authorize.twig
	'Authorize %s' => 'Autoriser %s',
	'Do you want to issue a pseudo-credential?' => 'Vil du at din konto skal brukes for koble denne enheten til WiFI-nettverket ?',
	'Approve' => 'Tillat',
	'Why is this needed?' => 'Hvorfor trengs dette?',
	'Requiring a manual step prevents automated enrollment.' => 'Ved å trykke tillatt, tillater du at applikasjonen mottar WiFi-profiler på dine vegne.',
	'Select your user realm' => 'Vennligst velg brukergruppe for å fortsette',
	'Continue' => 'Fortsett',

	'apple-mobileconfig instructions' => 'Etter at du åpner filen i MacOS, installer den ved å åpne <strong>Enhetsadministrering</strong> programmet, trykk <strong>Profil nedlastet</strong> og dobbelttrykk den nye profilen.',
	'google-onc instructions' => 'Etter at du har lastet ned profilen, åpne Chrome-nettleseren gå til denne URL-en: <a href="chrome://network">chrome://network</a>. Bruk <strong>Importer ONC-fil</strong> knappen. Importeringen er usynlig; innstillingene vil bli lagt til.',

	// filenames for localised store badges
	'Download from the Microsoft Store' => 'Last ned fra Microsoft Store',
	'en-us%%20%s.svg' => 'nn%%20%s.svg', // Microsoft has no bokmål, only nynorsk

	'Get it on F-Droid' => 'Tilgjengelig på F-Droid',
	'get-it-on-en.svg' => 'get-it-on-no.svg',

	'Download on the App Store' => 'Last ned fra App Store',
	'Download_on_the_App_Store_Badge_US-UK_RGB_blk_092917.svg' => 'Download_on_the_App_Store_Badge_NO_RGB_blk_100317.svg',

	'Get it on Google Play' => 'Last ned på Google Play',
	'GetItOnGooglePlay_Badge_Web_color_English.svg' => 'GetItOnGooglePlay_Badge_Web_color_Norwegian.svg',

	'Get it on Flathub' => 'Last ned fra Flathub',
	'flathub-badge-en.svg' => 'flathub-badge-nb.svg',
];
