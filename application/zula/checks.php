<?php

/**
 * Zula Framework Start Up Checks
 * --- Checks PHP version is correct and install directory is not present.
 * Here we are checking whether the mbstring PHP extension is installed and
 * configured properly. We also set the constant UNICODE_MBSTRING accordingly
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @author Evangelos Foutras
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula
 */

	if ( !version_compare( '5.2.0', PHP_VERSION, '<=' ) ) {
		zula_fatal_error( 'Error - Zula Framework',
						  '<h1>Required Minimum PHP Version: 5.2.0</h1>
						   <p><strong>Installed PHP version ('.PHP_VERSION.') does not meet the minimum requirement for the Zula Framework (5.2.0)</strong></p>
						   <p>Please install a version of PHP that meets the minimum requirements.</p>'
						);
	} else if ( is_dir( './install' ) && is_readable( './install' ) ) {
		$base = trim( dirname( $_SERVER['SCRIPT_NAME'] ), './\ ' );
		$base = empty( $base ) ? '/' : '/'.rtrim( $base, '/' ).'/';
		zula_fatal_error( 'Installation - '._PROJECT_NAME,
							'<h1>Install directory is still present</h1>
							 <p>If you have not yet installed '._PROJECT_NAME.', or need to upgrade, you can do so by going to the following directory:</p>
							 <p>Install/Upgrade Directory: <a href="'.$base.'install/">./install</a></p>
							 <hr>
							 <p>If you have already installed '._PROJECT_NAME.', then please remove the install directory for security reasons before you can continue.</p>'
						);
	}

	// Check whether PCRE has been compiled with UTF-8 support
	if ( preg_match( '/^.{1}$/u', "ñ", $UTF8_ar ) != 1 ) {
		zula_fatal_error( 'Error - Zula Framework',
						  '<h1>PCRE is not compiled with UTF-8 support</h1>
						   <p>The PCRE library in your PHP installation is not compiled with UTF-8 support. Make sure you are using the PCRE library supplied by PHP.</p>
						   <p>Please refer to the <a href="http://php.net/pcre">PHP PCRE documentation</a> for more information.</p>'
						);
	} else if ( !extension_loaded( 'mbstring' ) ) {
		define( 'UNICODE_MBSTRING', false );
	} else {
		// Check mbstring's configuration
		switch( true ) {
			case ini_get( 'mbstring.func_overload' ) != 0:
				$errTitle = 'Multibyte string function overloading is enabled';
				$errSetting = 'mbstring.func_overload';
				break;

			case ini_get( 'mbstring.encoding_translation' ) != 0:
				$errTitle = 'Multibyte string input conversion is enabled';
				$errSetting = 'mbstring.encoding_translation';
				break;

			case ini_get( 'mbstring.http_input' ) != 'pass':
				$errTitle = 'Multibyte string input conversion is enabled';
				$errSetting = 'mbstring.http_input';
				break;

			case ini_get( 'mbstring.http_output' ) != 'pass':
				$errTitle = 'Multibyte string output conversion is enabled';
				$errSetting = 'mbstring.http_output';
		}
		if ( isset( $errTitle, $errSetting ) ) {
			// mbstring extension not configured correctly
			zula_fatal_error( 'Error - Zula Framework',
								'<h1>'.$errTitle.'</h1>
								 <p>Check the <em>'.$errSetting.'</em> setting in your php.ini.</p>
								 <p>Please refer to the <a href="http://www.php.net/mbstring">PHP mbstring documentation</a> for more information.</p>'
							);
		} else {
			// The mbstring extension is loaded and ready to shoot :P
			mb_internal_encoding( 'UTF-8' );
			mb_language( 'uni' );
			define( 'UNICODE_MBSTRING', true );
		}
	}

?>
