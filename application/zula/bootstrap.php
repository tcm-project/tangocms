<?php

/**
 * Zula Framework Bootstrap
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula
 */

	try {
		$rawRequestPath = Registry::get('input')->get('url');
		if ( strpos( $rawRequestPath, 'assets/v/' ) === 0 ) {
			// Hard coded 'assets' URL route for simple file pass-thru
			return require 'assets.php';
		}
	} catch ( Input_KeyNoExist $e ) {
	}

	/**
	 * Check if SQL is enable, if so attempt to connect to the server provided
	 * in the main configuration files. Extra configuration values will be loaded
	 * as well from the correct table, and merged into the main configuration
	 * object
	 */
	if ( $config->has( 'sql/enable' ) && $config->get( 'sql/enable' ) ) {
		if ( !extension_loaded( 'pdo' ) ) {
			throw new Exception( 'PDO extension is currently not loaded' );
		}
		$sql = $config->get( 'sql' );
		$sqlDriverOpts = array(
								PDO::ATTR_PERSISTENT	=> isset($sql['persistent']) ? (bool) $sql['persistent'] : false,
								PDO::ATTR_ERRMODE		=> PDO::ERRMODE_EXCEPTION,
								);
		$sqlConnection = new Sql( $sql['type'], $sql['database'], $sql['host'],
								  $sql['user'], $sql['pass'], $sql['port'], $sqlDriverOpts );
		Registry::register( 'sql', $sqlConnection );
		$sqlConnection->setPrefix( $sql['prefix'] );
		$sqlConnection->query( 'SET NAMES "utf8"' ); # Use UTF-8 character set for the connection
		unset( $sqlConnection );
		// Attempt to load the SQL configuration details
		$configSql = new Config_sql;
		$configSql->load( 'config' );
		Registry::register( 'config_sql', $configSql );
		$config->load( $configSql );
	}

	// Update date configuration details
	try {
		$date = Registry::get( 'date' );
		foreach( $config->get( 'date' ) as $key=>$val ) {
			switch( $key ) {
				case 'format':
					$date->setFormat( $val );
					break;

				case 'use_relative':
					$date->useRelative( $val );
					break;

				case 'timezone':
					$date->changeTimezone( $val );
					break;

				default:
					Registry::get( 'log' )->message( 'unknown date configuration key "'.$key.'"', Log::L_WARNING );
			}
		}
	} catch ( Config_KeyNoExist $e ) {}

	$session = $zula->loadLib( 'session' );
	$dispatcher = Registry::get( 'dispatcher' );
	if ( _APP_MODE == 'installation' ) {
		/**
		 * Load some installation specific files as there may be things that need
		 * changing/adding upon installation/upgrading of Zula/TCM versions
		 */
		require $zula->getDir( 'zula' ).'/install.php';
	} else {
		$zula->loadLib( 'ugmanager' );
		try {
			$uid = $session->identify( $_SESSION['auth']['key'], $_SESSION['auth']['for'] );
			if ( $uid === false ) {
				throw new Exception;
			}
		} catch ( Exception $e ) {
			$uid = $session->identify(); # Identify as guest for fail safe
		}
	}
	define( '_ACL_ENABLED', ($config->has( 'acl/enable' ) && $config->get( 'acl/enable' )) );
	if ( Registry::has( 'sql' ) ) {
		$acl = $zula->loadLib( 'acl' );
	}
	Hooks::load();
	$router = $zula->loadLib( 'router' );

	/**
	 * Microsoft Web App Gallery (Feature #221) support.
	 */
	if ( strtoupper( substr(PHP_OS, 0, 3) ) === 'WIN' && file_exists('msInstall.php') ) {
		return require 'msInstall.php';
	}

	/**
	 * Main loading of the correct theme and requested controller
	 */
	Hooks::notifyAll( 'bootstrap_pre_request', _AJAX_REQUEST );
	if ( _AJAX_REQUEST === false && $config->has( 'theme/use_global' ) && $config->get( 'theme/use_global' ) ) {
		if ( _APP_MODE == 'installation' ) {
			$themeName = 'carbon';
		} else {
			$themeName = Theme::getSiteTypeTheme();
			if ( $config->get( 'theme/allow_user_override' ) ) {
				$userTheme = Registry::get( 'session' )->getUser( 'theme' );
				if ( $userTheme != 'default' && Theme::exists( $userTheme ) ) {
					$themeName = $userTheme;
				}
			}
		}
		define( '_THEME_NAME', $themeName );
		try {
			$theme = new Theme( $themeName );
			Registry::register( 'theme', $theme );
			$dispatchContent = $dispatcher->dispatch();
			if ( $dispatchContent !== false ) {
				header( 'Content-Type: text/html; charset=utf-8' );
				if ( $dispatcher->isStandalone() ) {
					// Load stand alone module with no other theme, modules etc
					echo $dispatchContent;
				} else {
					// Include a themes init file, to allow a theme to configure some things
					$initFile = $zula->getDir( 'themes' ).'/'.$themeName.'/init.php';
					if ( is_readable( $initFile ) ) {
						include $initFile;
					}
					/**
					 * Load the requested controller into the SC tag, then load all other
					 * controllers for the sectors. Once done then output the complete theme
					 */
					$theme->loadIntoSector( 'SC', $dispatchContent );
					$theme->loadSectorControllers();
					$output = $theme;
				}
			} else {
				$output = false;
			}
		} catch ( Theme_NoExist $e ) {
			Registry::get( 'log' )->message( $e->getMessage(), Log::L_WARNING );
			trigger_error( 'Required theme "'.$themeName.'" does not exist', E_USER_WARNING );
			$output = $dispatcher->dispatch();
		}
	} else {
		Registry::get( 'log' )->message( 'loading cntrlr without global theme, possibly due to AJAX request', Log::L_DEBUG );
		$output = $dispatcher->dispatch( _AJAX_REQUEST );
	}

	Hooks::notifyAll( 'bootstrap_loaded', _AJAX_REQUEST, (isset($output) && $output instanceof Theme) );
	if ( isset( $output ) ) {
		if ( $output instanceof Theme ) {
			echo $output->output();
		} else if ( $output !== false ) {
			echo $output;
		}
	}
	return true;

?>
