<?php
\spl_autoload_extensions( '.php' );
\spl_autoload_register( 'spl_autoload' );

// We install the software in /usr/share/php/letswifi,
// and the namespace prefix is letswifi, so the fully qualified
// path name is the class name with /usr/share/php prefixed
\set_include_path( '/usr/share/php/' );

// Twig is incompatible with SPL (Standard PHP Library) autoloading.
// It requires the use of its own autoloader.
require '/usr/share/php/Twig3/autoload.php';
