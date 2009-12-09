<?php

/**
 * Zula Framework index (collector/front controller)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula
 */

	$sTime = microtime( true );

	/**
	 * Set some constants such as path to application folder as well as
	 * the appliaction mode to run in
	 *
	 * The following constants *MAY* already be defined, for example
	 * in the ./install/index.php file (So it does make sense to check :P)
	 *
	 * _PROJECT_ID should be a string containing A-Z, a-z, _ or - and is used
	 * as a identifier to the project that is using Zula, for example the
	 * TextDomain of controllers/modules will be prefixed with this value
	 * followed by a hypthen '-'.
	 */
	define( '_PROJECT_ID', 'tangocms' );
	define( '_PROJECT_NAME', 'TangoCMS' );

	if ( !defined( '_PATH_APPLICATION' ) ) {
		define( '_PATH_APPLICATION', dirname(__FILE__).'/application' );
	}
	if ( !defined( '_APP_MODE' ) ) {
		define( '_APP_MODE', 'development' );
	}

	/**
	 * Include common functions, 'starup' checks such as PHP version check
	 * and also some Unicode checks.
	 */
	require _PATH_APPLICATION.'/zula/common.php';
	include _PATH_APPLICATION.'/zula/checks.php';

	// Load and get an instance of the base Zula framework class
	require _PATH_APPLICATION.'/zula/zula.php';
	$zula = Zula::getInstance();

	/**
	 * Default directories in Zula should be fine, though you can configure
	 * them from here if you want, via $zula->updateDir(); EG:
	 * $zula->updateDir( 'lib', '/libaries' );
	 */
	$zula->updateDir( 'config', dirname(__FILE__).'/config' );
	$zula->updateDir( 'uploads', './assets/uploads' );

	if ( _APP_MODE == 'installation' ) {
		define( '_REAL_MODULE_DIR', $zula->getDir( 'modules' ) );
		// Reconfigure some directories so they work correctly when installing
		$zula->updateDir( 'modules', './modules' );
		$zula->updateDir( 'themes', './themes' );
		$zula->updateDir( 'assets', '../assets' );
		$zula->updateDir( 'js', '../assets/js' );
		$zula->updateDir( 'tmp', '../tmp' );
		$zula->updateDir( 'install', './' );
		$zula->updateDir( 'uploads', '../assets/uploads' );
		$zula->updateDir( 'config', '../config' );
	}

	Registry::register( 'zula', $zula );

	if ( UNICODE_MBSTRING === false ) {
		// Load needed unicode libraries
		require $zula->getDir( '3rd_party' ).'/phputf8/utils/unicode.php';
		require $zula->getDir( '3rd_party' ).'/phputf8/native/core.php';
	}

	/**
	 * Work out the path to the config directory, based upon the server name
	 * If the directory does not exist, then it will fall back to the
	 * default directory.
	 */
	$confDir = $zula->getDir( 'config' ).'/default';
	if ( PHP_SAPI != 'cli' ) {
		$serverName = $_SERVER['SERVER_NAME'];
		if ( strlen( _BASE_DIR ) != 1 ) {
			$serverName .= _BASE_DIR;
		}
		$serverName = str_replace( '/', '.', rtrim($serverName, '/') );
		if ( strlen( $serverName ) ) {			
			if ( substr( $serverName, -8 ) == '.install' ) {
				$serverName = substr( $serverName, 0, -8 );
			}
			if ( is_dir( $zula->getDir( 'config' ).'/'.$serverName ) ) {
				$confDir = $zula->getDir( 'config' ).'/'.$serverName;
			}
		}
	}
	$zula->updateDir( 'config', $confDir );
	// Load the main configuration file for the project and define version
	$config = $zula->loadMainConfig( $zula->getDir( 'config' ).'/config.ini.php' );
	define( '_PROJECT_VERSION', $config->get( 'config/version' ) );

	/**
	 * Load the default libraries that are most commonly needed if you wish to
	 * load more, then simply use $zula->load_lib()
	 */
	$zula->loadDefaultLibs();
	Module::setDirectory( $zula->getDir( 'modules' ) );

	// Bootstrap
	require _PATH_APPLICATION.'/zula/bootstrap.php';

	/**
	 * No method chaining, or class constant here, as PHP4 syntax
	 * error will occur. Normally, 1 would be Log::L_DEBUG
	 */
	$log = Registry::get( 'log' );
	$log->message( sprintf('Zula request finished in %f seconds', microtime(true)-$sTime), 1 );

?>
