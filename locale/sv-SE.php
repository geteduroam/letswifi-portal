<?php declare( strict_types=1 );

return [
	'sv-SE' => 'Svenska',

	// Pages showing apps and profiles for different platforms
	'If you cannot use the official app, you can download an installation profile for manual installation.' => 'Om du inte kan använda den officiella appen kan du ladda ner en installationsprofil för manuell installation.',
	'There is no app available for %s.' => 'Det finns ingen app tillgänglig för %s.',
	'Download an installation profile for manual installation.' => 'Ladda ner en installationsprofil för manuell installation.',
	'Other options' => 'Andra alternativ',
	'Advanced options' => 'Avancerade alterantiv',
	'Other platforms and advanced options' => 'Alternativ för andra plattformar och avancerade användare',
	'Generate a certificate for manual use' => 'Generera ett certifikat för manuell användning',

	// base.twig
	'Language' => 'Språk',
	'Account' => 'Konto',
	'Login' => 'Logga in',
	'Logout' => 'Logga ut',
	'Account information' => 'Kontoinformation',

	// start.twig
	'Welcome to %1$s at %2$s' => 'Välkommen till %1$s på %2$s',
	'To use %1$s at %2$s, download the app or profile for your device below.' => 'För att använda %1$s på %2$s, ladda ner appen eller profilen för din enhet nedan.',
	'Download the %s app to configure your device.' => 'Ladda ner appen %s för att konfigurera din enhet.',
	'View apps and profiles for all platforms' => 'Visa appar och profiler för alla plattformar',
	'login required' => 'inloggning krävs',

	// app.twig
	'Apps' => 'Appar',
	'All installer apps' => 'Alla installationsappar',

	// realm-picker.twig
	'Realm' => 'Domän',

	// profile-download.twig
	'Profile download' => 'Profilnedladdning',
	'Download %s profile' => 'Ladda ner %s-profil',
	'Download starting' => 'Din nedladdning börjar strax',
	'Download not starting?' => 'Startar inte nedladdningen?',
	'Start download' => 'Starta nedladdning',
	'Use passphrase when prompted:' => 'När du uppmanas att ange en lösenfras under installationen, ange följande lösenfras:',

	// profile-advanced.twig
	'Download the app' => 'Ladda ner appen',
	'We recommend that you use the app' => 'För de flesta användare är det enklast att använda en av de officiella apparna.',
	'Manual certificate creation' => 'Manuell certifikat-skapning',
	'Manual advanced profile creation' => 'Manuell avancerad profilskapning',
	'Create configuration profile' => 'Skapa konfigurationsprofil',
	'Alternatively, you can use a configuration profile' => 'För avancerade användare, eller på en enhet där ingen app ännu finns tillgänglig, är det också möjligt att ladda ner en konfigurationsprofil.',
	'Encryption' => 'Kryptering',
	'When encrypting you need a passphrase when installing' => 'Om du krypterar din profil måste du ange lösenfrasen för att dekryptera innehållet vid installation.',
	'Passphrase is only needed during installation' => 'Efter att profilen har installerats behövs inte lösenfrasen längre; den används endast under installationen för att dekryptera profilinnehållet.',
	'Use the feature depending encryption support on your system' => 'Använd det här alternativet beroende på om ditt system stöder krypterade eller okrypterade profiler.',
	'Enter passphrase for encryption' => 'Ange en lösenfras för att kryptera profilen',
	'advanced' => 'avancerat',
	'optional' => 'valfritt',

	// error.twig
	'An error occurred' => 'Ett fel uppstod',
	'Debug info' => 'Detaljerad felrapport',
	'Contact helpdesk' => 'Kontakta din helpdesk för att få hjälp',

	// me.twig
	'User ID' => 'Användarnamn',
	'Affiliations' => 'Affilieringar',
	'User information is not stored after you log out.' => 'Användarinformation lagras inte efter att du loggat ut.',
	'User ID is connected to credentials while they are valid and short time thereafter.' => 'Användarnamn är kopplat till inloggningsuppgifter så länge de är giltiga och en kort tid därefter.',
	'Available realms' => 'Tillgängliga domäner',
	'No realms available' => 'Inga domäner tillgängliga',
	'Authorised applications' => 'Auktoriserade applikationer',
	'No authorised applications' => 'Inga auktoriserade applikationer',
	'Client ID' => 'Klient-ID',
	'Issued' => 'Utfärdat',
	'Expires' => 'Går ut',
	'Revoke' => 'Återkalla',
	'Credentials' => 'Inloggningsuppgifter',
	'Credential' => 'Inloggningsuppgift',
	'No credentials' => 'Inga inloggningsuppgifter',

	// authorize.twig
	'Authorize %s' => 'Auktorisera %s',
	'Do you want to issue a pseudo-credential?' => 'Vill du använda ditt konto för att ansluta den här enheten till Wi-Fi-nätverket?',
	'Approve' => 'Godkänn',
	'Why is this needed?' => 'Varför behövs detta?',
	'Requiring a manual step prevents automated enrollment.' => 'Genom att klicka på godkänn tillåter du applikationen att ta emot Wi-Fi-profiler för din räkning.',
	'Select your user realm' => 'Välj din användargrupp för att fortsätta',
	'Continue' => 'Fortsätt',

	'apple-mobileconfig instructions' => 'Efter att du öppnat filen på MacOS, installera det genom att öppna appen <strong>Systeminställningar</strong>, klicka på <strong>Profil nedladdad</strong> och dubbelklicka sedan på den nya profilen.',
	'google-onc instructions' => 'Efter att du laddat ner filen, öppna Chrome-webbläsaren och gå till denna URL: <a href="chrome://network">chrome://network</a>. Använd sedan knappen <strong>Importera ONC-fil</strong>. Importen sker tyst; de nya nätverksdefinitionerna läggs till i de föredragna nätverken.',

	// filenames for localised store badges
	'Download from the Microsoft Store' => 'Ladda ned från Microsoft Store',
	'en-us%%20%s.svg' => 'sv%%20%s.svg',

	'Get it on F-Droid' => 'Ladda ned på F-Droid',
	'get-it-on-en.svg' => 'get-it-on-sv.svg',

	'Download on the App Store' => 'Hämta i App Store',
	'Download_on_the_App_Store_Badge_US-UK_RGB_blk_092917.svg' => 'Download_on_the_App_Store_Badge_SE_RGB_blk_100317.svg',

	'Get it on Google Play' => 'Ladda ned på Google Play',
	'Google_Play_Store_badge_EN.svg' => 'GetItOnGooglePlay_Badge_Web_color_Swedish.svg',

	'Get it on Flathub' => 'Finns på Flathub',
	'badge-en.svg' => 'badge-sv.svg',
];
