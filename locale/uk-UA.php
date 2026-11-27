<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * SPDX-License-Identifier: BSD-3-Clause
 */

return [
	'uk-UA' => 'Українська',

	// Pages showing apps and profiles for different platforms
	'If you cannot use the official app, you can download an installation profile for manual installation.' => 'Якщо ви не можете скористатися офіційним додатком, ви також можете завантажити профіль встановлення для ручного встановлення.',
	'There is no app available for %s.' => 'Для %s немає доступного додатка.',
	'Download an installation profile for manual installation.' => 'Завантажте профіль встановлення для ручного встановлення.',
	'Other options' => 'Інші варіанти',
	'Advanced options' => 'Розширені параметри',
	'Other platforms and advanced options' => 'Інші платформи та розширені параметри',
	'Generate a certificate for manual use' => 'Створити сертифікат для ручного використання',

	// base.twig
	'Language' => 'Мова',
	'Account' => 'Обліковий запис',
	'Login' => 'Вхід',
	'Logout' => 'Вихід',
	'Account information' => 'Інформація про обліковий запис',

	// start.twig
	'Welcome to %1$s at %2$s' => 'Ласкаво просимо до %1$s у %2$s',
	'To use %1$s at %2$s, download the app or profile for your device below.' => 'Щоб використовувати %1$s у %2$s, завантажте додаток або профіль для вашого пристрою нижче.',
	'Download the %s app to configure your device.' => 'Завантажте додаток %s, щоб налаштувати ваш пристрій.',
	'View apps and profiles for all platforms' => 'Переглянути додатки та профілі для всіх платформ',
	'login required' => 'потрібен вхід',

	// app.twig
	'Apps' => 'Додатки',
	'All installer apps' => 'Усі додатки для встановлення',

	// realm-picker.twig
	'Realm' => 'Сфера (Realm)',

	// profile-download.twig
	'Profile download' => 'Завантаження профілю',
	'Download %s profile' => 'Завантажити профіль %s',
	'Download starting' => 'Завантаження розпочнеться незабаром',
	'Download not starting?' => 'Завантаження не розпочалося?',
	'Start download' => 'Почати завантаження',
	'Use passphrase when prompted:' => 'Коли під час встановлення вас попросять ввести пароль, використовуйте наступний:',

	// profile-advanced.twig
	'Download the app' => 'Завантажити додаток',
	'We recommend that you use the app' => 'Для більшості користувачів найпростішим варіантом є використання одного з офіційних додатків.',
	'Manual advanced profile creation' => 'Ручне створення розширеного профілю',
	'Create configuration profile' => 'Створити профіль конфігурації',
	'Alternatively, you can use a configuration profile' => 'Для досвідчених користувачів або для пристроїв, для яких ще немає додатка, також можна завантажити профіль конфігурації.',
	'Encryption' => 'Шифрування',
	'When encrypting you need a passphrase when installing' => 'Шифрування профілю вимагає введення пароля для розшифрування вмісту профілю.',
	'Passphrase is only needed during installation' => 'Після встановлення профілю пароль більше не потрібен; він використовується лише під час встановлення для розшифрування вмісту профілю.',
	'Use the feature depending encryption support on your system' => 'Виберіть цей варіант залежно від того, чи підтримує ваша система зашифровані або незашифровані профілі.',
	'Enter passphrase for encryption' => 'Введіть пароль для шифрування профілю',
	'advanced' => 'розширений',
	'optional' => 'необов\'язково',

	// error.twig
	'An error occurred' => 'Сталася помилка',
	'Debug info' => 'Детальний звіт про помилку (завдяки увімкненому режиму налагодження)',
	'Contact helpdesk' => 'Зверніться до служби підтримки за допомогою',

	// me.twig
	'User ID' => 'ID користувача',
	'Affiliations' => 'Приналежності',
	'User information is not stored after you log out.' => 'Інформація про користувача не зберігається після вашого виходу.',
	'User ID is connected to credentials while they are valid and short time thereafter.' => 'ID користувача пов\'язаний з обліковими даними, поки вони дійсні, і протягом короткого часу після цього.',
	'Available realms' => 'Доступні сфери',
	'No realms available' => 'Немає доступних сфер',
	'Authorised applications' => 'Авторизовані додатки',
	'No authorised applications' => 'Немає авторизованих додатків',
	'Client ID' => 'ID клієнта',
	'Issued' => 'Видано',
	'Expires' => 'Термін дії',
	'Revoke' => 'Відкликати',
	'Credentials' => 'Облікові дані',
	'Credential' => 'Облікові дані',
	'No credentials' => 'Немає облікових даних',

	// authorize.twig
	'Authorize %s' => 'Авторизувати %s',
	'Do you want to issue a pseudo-credential?' => 'Ви хочете використовувати свій обліковий запис для підключення цього пристрою до мережі Wi-Fi?',
	'Approve' => 'Схвалити',
	'Why is this needed?' => 'Навіщо це потрібно?',
	'Requiring a manual step prevents automated enrollment.' => 'Натиснувши «Схвалити», ви дозволяєте додатку отримувати профілі Wi-Fi від вашого імені.',
	'Select your user realm' => 'Будь ласка, виберіть вашу групу користувачів, щоб продовжити',
	'Continue' => 'Продовжити',

	'apple-mobileconfig instructions' => 'Після відкриття файлу на MacOS встановіть його, відкривши додаток <strong>Системні параметри</strong>, натиснувши <strong>Завантажений профіль</strong>, а потім двічі клацнувши на новому профілі.',
	'google-onc instructions' => 'Після завантаження файлу відкрийте браузер Chrome і перейдіть за цією адресою: <a href="chrome://network">chrome://network</a>. Потім натисніть кнопку <strong>Import ONC file</strong>. Імпорт відбувається непомітно; нові визначення мережі будуть додані до пріоритетних мереж.',

	// admin-credentials.twig
	'admin_filter_heading' => 'Фільтр',
	'admin_filter_valid_on' => 'Дійсний до',
	'admin_filter_ident' => 'Ідентифікатор',
	'admin_filter_requester' => 'Запитувач',
	'admin_filter_mask' => 'Маска',
	'admin_filter_show' => 'Відображення',
	'admin_filter_mask_revoked' => 'відкликано',
	'admin_filter_mask_unrevoked' => 'не відкликано',
	'admin_filter_show_times' => 'мітки часу',
	'admin_filter_show_useragent' => 'user agent',
	'admin_filter_show_clientid' => 'id клієнта',
	'admin_filter_submit' => 'Фільтрувати',

	'admin_table_credentials_header_ident' => 'Ідентифікатор',
	'admin_table_credentials_header_requester' => 'Запитувач',
	'admin_table_credentials_header_user_agent' => 'User agent',
	'admin_table_credentials_header_client_id' => 'ID клієнта',
	'admin_table_credentials_header_issued' => 'Видано',
	'admin_table_credentials_header_expires' => 'Термін дії',
	'admin_table_credentials_header_revoked' => 'Відкликано',
	'admin_table_credentials_button_revoke' => 'Відкликати',

	'admin_dialog_revoke_credential_caption' => 'Ви впевнені, що хочете відкликати наступні облікові дані?',
	'admin_dialog_revoke_credential_ident' => 'Ідентифікатор',
	'admin_dialog_revoke_credential_requester' => 'Запитувач',
	'admin_dialog_revoke_credential_button_revoke' => 'Відкликати',
	'admin_dialog_revoke_credential_button_cancel' => 'Скасувати',

	// filenames for localised store badges
	'Download from the Microsoft Store' => 'Завантажити з Microsoft Store',
	'en-us%%20%s.svg' => 'en-us%%20%s.svg',

	'Get it on F-Droid' => 'Отримати у F-Droid',
	'get-it-on-en.svg' => 'get-it-on-en.svg',

	'Download on the App Store' => 'Завантажити з App Store',
	'Download_on_the_App_Store_Badge_US-UK_RGB_blk_092917.svg' => 'Download_on_the_App_Store_Badge_US-UK_RGB_blk_092917.svg',

	'Get it on Google Play' => 'Отримати у Google Play',
	'GetItOnGooglePlay_Badge_Web_color_English.svg' => 'GetItOnGooglePlay_Badge_Web_color_English.svg',

	'Get it on Flathub' => 'Отримати у Flathub',
	'flathub-badge-en.svg' => 'flathub-badge-en.svg',
];
