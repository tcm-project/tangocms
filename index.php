<?php

/**
 * Zula Framework index (collector/front controller)
 *
 * Please do not use any PHP5 specific syntax within this file, other wise a PHP4
 * syntax error will occur. Instead we want to display our own, nicer error message
 * to users of PHP4.
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula
 */

	$sTime = microtime( true );

	/**
	 * _PROJECT_ID should be a string containing A-Z, a-z, _ or - and is used
	 * as a identifier to the project that is using Zula, for example the
	 * TextDomain of controllers/modules will be prefixed with this value
	 * followed by a hypthen '-'.
	 */
	define( '_PROJECT_ID', 'tangocms' );
	define( '_PROJECT_NAME', 'TangoCMS' );

	/**
	 * Include common functions, 'starup' checks such as PHP version check
	 * and also some Unicode checks.
	 */
	require 'application/zula/common.php';
	include 'application/zula/checks.php';

	// Load and get an instance of the base Zula framework class
	require 'application/zula/zula.php';
	$zula = Zula::getInstance( dirname(__FILE__), (isset($state) ? $state : 'development') );
	Registry::register( 'zula', $zula );

	// _AJAX_REQUEST is DEPRECATED please use Zula::getMode() instead
	define( '_AJAX_REQUEST', $zula->getMode() == 'standalone' );

	if ( UNICODE_MBSTRING === false ) {
		// Load needed unicode libraries
		require $zula->getDir( '3rd_party' ).'/phputf8/utils/unicode.php';
		require $zula->getDir( '3rd_party' ).'/phputf8/native/core.php';
	}

	/**
	 * Default directories in Zula should be fine, though you can configure
	 * them from here if you want, via Zula::setDir()
	 */
	if ( $zula->getState() == 'installation' ) {
		define( '_REAL_MODULE_DIR', $zula->getDir( 'modules' ) );
		// Reconfigure some directories so they work correctly when installing
		$zula->setDir( 'modules', './modules' );
		$zula->setDir( 'themes', './themes' );
		$zula->setDir( 'assets', '../assets' );
		$zula->setDir( 'js', '../assets/js' );
		$zula->setDir( 'tmp', '../tmp' );
		$zula->setDir( 'install', './' );
		$zula->setDir( 'uploads', '../assets/uploads' );
		$zula->setDir( 'config', '../config' );
	}

	$input = $zula->loadLib( 'input' ); # Early loading of a default lib

	/**
	 * Get the correct config name to set the config directory, either based
	 * upon the server name or the provided CLI configuration.
	 */
	$configName = 'default';
	if ( $zula->getMode() == 'cli' ) {
		$configName = $input->cli( 'config' );
	} else {
		$serverName = $_SERVER['SERVER_NAME'];
		if ( strlen( _BASE_DIR ) != 1 ) {
			$serverName .= _BASE_DIR;
		}
		$serverName = str_replace( '/', '.', rtrim($serverName, '/') );
		if ( strlen( $serverName ) ) {
			if ( substr( $serverName, -8 ) == '.install' ) {
				$serverName = substr( $serverName, 0, -8 );
			}
			if ( strpos( $serverName, 'www.' ) === 0 ) {
				$serverName = substr( $serverName, 4 );
			}
			if ( is_dir( $zula->getDir( 'config' ).'/'.$serverName ) ) {
				$configName = $serverName;
			}
		}
	}
	$zula->setDir( 'config', $zula->getDir('config').'/'.$configName );
	// Load the main configuration file for the project and define version
	$config = $zula->loadMainConfig( $zula->getDir( 'config' ).'/config.ini.php' );
	define( '_PROJECT_VERSION', $config->get( 'config/version' ) );

	// Load the default libraries that are most commonly needed
	$zula->loadDefaultLibs();
	Registry::get( 'i18n' )->setLocale( $config->get('locale/default') );
	Module::setDirectory( $zula->getDir( 'modules' ) );

	/**
	 * Finally run the bootstrap to handle our request, and exit correctly
	 */
	$status = require 'application/zula/bootstrap.php';
	$msg = sprintf( 'Zula request finished %1$s in %2$f seconds',
					($status ? 'successfully' : 'unsuccessfully'),
					microtime(true)-$sTime );
	$log = Registry::get( 'log' );
	$log->message( $msg, 1 );

	exit( $zula->getExitCode() );

?>
