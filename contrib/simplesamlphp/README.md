# SimpleSAMLphp username/password theme

A theme that gives the SimpleSAMLphp login page the same look and feel as the geteduroam approve screen.


Copy the `modules/themegeteduroam` directory into your `modules` directory in SimpleSAMLphp

In your SimpleSAMLphp `config.php`, enable the module

	'module.enable' => [
		// other modules
		'themegeteduroam' => true,
	],

and set the theme

	'theme.use' => 'themegeteduroam:geteduroam',
	'theme.header' => 'geteduroam',
