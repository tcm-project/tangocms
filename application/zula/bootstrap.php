<?php

/**
 * Zula Framework Bootstrap
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula
 */

	try {
		$rawRequestPath = $input->get('url');
	} catch ( Input_KeyNoExist $e ) {
		$rawRequestPath = null;
	}
	if ( strpos( $rawRequestPath, 'assets/v/' ) === 0 ) {
		// Hard coded 'assets' URL route for simple file pass-thru
		return require 'assets.php';
	}
	$session = $zula->loadLib( 'session' );
	if ( strpos( $rawRequestPath, 'antispam/captcha/' ) === 0 ) {
		// Hard coded 'antispam/captcha/{id}' route to display captcha image
		return require 'antispamCaptcha.php';
	}

	/**
	 * Check if SQL is enabled, if so attempt to connect to the server provided in the main
	 * configuration files. Extra configuration values will be loaded as well from the correct
	 * table, and merged into the main configuration object
	 */
	if ( $config->get( 'sql/enable' ) ) {
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
		if ( $sql['type'] == 'mysql' ) {
			# Use UTF-8 character set for the connection
			$sqlConnection->query( 'SET NAMES "utf8"' );
		}
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
					$date->setTimezone( $val );
					break;
			}
		}
	} catch ( Config_KeyNoExist $e ) {}

	if ( $zula->getState() == 'setup' ) {
		/**
		 * Load some setup specific files as there may be things that need
		 * changing/adding upon installation/upgrading of application versions
		 */
		require $zula->getDir( 'zula' ).'/setup.php';
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
	define( '_ACL_ENABLED', (bool) $config->get( 'acl/enable' ) );
	if ( Registry::has( 'sql' ) ) {
		$acl = $zula->loadLib( 'acl' );
	}
	Hooks::load();

	// Load the main router of the correct type
	if ( $input->has( 'get', 'ns' ) || (function_exists( 'apache_get_modules' ) && !in_array('mod_rewrite', apache_get_modules())) ) {
		$router = new Router;
	} else {
		$router = new Router( $config->get('url_router/type') );
	}
	Registry::register( 'router', $router );

	/**
	 * Microsoft Web App Gallery (Feature #221) support.
	 */
	if ( strtoupper( substr(PHP_OS, 0, 3) ) === 'WIN' && file_exists('msInstall.php') ) {
		return require 'msInstall.php';
	}

	/**
	 * Configure the dispatcher, then get data from the parsed URL to load; if there is
	 * not enough data simply use the frontpage sc layout for the current site type
	 */
	Hooks::notifyAll( 'bootstrap_pre_request' );
	$dispatcher = new Dispatcher;
	$dispatcher->setDisplayErrors( ($zula->getMode() == 'normal') ) # Display dispatchers own error msgs
			   ->setStatusHeader( ($zula->getMode() != 'cli') );
	Registry::register( 'dispatcher', $dispatcher ); # For compatibility, not sure if we should still have this

	$requestedUrl = $router->getParsedUrl();
	if ( $requestedUrl->module == null ) {
		// Load data fron the fpsc (Front Page Sector Content) layout
		if ( $zula->getState() == 'setup' ) {
			$fpsc = new Layout( $zula->getDir('setup').'/layout-fpsc.xml' );
		} else {
			$fpsc = new Layout( 'fpsc-'.$requestedUrl->siteType );
		}
		$fpscCntrlr = $fpsc->getControllers( 'SC' );
		$fpscCntrlr = array_shift( $fpscCntrlr );
		$requestedUrl->module( $fpscCntrlr['mod'] )
					 ->controller( $fpscCntrlr['con'] )
					 ->section( $fpscCntrlr['sec'] );
		$dispatchConfig = (array) $fpscCntrlr['config'];
	} else {
		$dispatchConfig = array();
	}

	if ( $zula->getMode() == 'normal' && $config->get( 'theme/use_global' ) ) {
		if ( $zula->getState() == 'setup' ) {
			$themeName = 'purity';
		} else {
			$themeName = $config->get( 'theme/'.$router->getSiteType().'_default' );
		}
		define( '_THEME_NAME', $themeName );
		try {
			$theme = new Theme( $themeName );
			try {
				$theme->setJsAggregation( $config->get('cache/js_aggregate') )
					  ->setGoogleCdn( $config->get('cache/google_cdn') );
			} catch ( Config_KeyNoExist $e ) {
			}
			Registry::register( 'theme', $theme );
			$dispatchContent = $dispatcher->dispatch( $requestedUrl, $dispatchConfig );
			if ( $dispatcher->getStatusCode() === 403 && $session->isLoggedIn() === false ) {
				// User did not have permission, fallback to 'session' module
				$dispatchContent = $dispatcher->dispatch( new Router_Url('session') );
			}
			if ( is_bool( $dispatchContent ) ) {
				$output = $dispatchContent;
			} else {
				header( 'Content-Type: text/html; charset=utf-8' );
				// Include a themes init file, to allow a theme to configure some things
				$initFile = $zula->getDir( 'themes' ).'/'.$themeName.'/init.php';
				if ( is_readable( $initFile ) ) {
					include $initFile;
				}
				/**
				 * Work out which layout to use with the theme, load all cntrlrs from that
				 * and then load the main dispatchers content
				 */
				if ( $zula->getState() == 'setup' ) {
					$layout = new Layout( $zula->getDir('setup').'/layout.xml' );
				} else {
					$layout = new Layout( Layout::find($requestedUrl->siteType, $router->getRawRequestPath()) );
				}
				$theme->loadDispatcher( $dispatchContent, $dispatcher );
				$theme->loadLayout( $layout );
				$output = $theme;
			}
		} catch ( Theme_NoExist $e ) {
			Registry::get( 'log' )->message( $e->getMessage(), Log::L_WARNING );
			trigger_error( 'required theme "'.$themeName.'" does not exist', E_USER_WARNING );
			$output = $dispatcher->dispatch( $requestedUrl, $dispatchConfig );
		}
	} else {
		$output = $dispatcher->dispatch( $requestedUrl, $dispatchConfig );
		if ( $zula->getMode() == 'cli' ) {
			// Display a friendly message to stdout
			switch( $dispatcher->getStatusCode() ) {
				case 200:
					if ( !is_bool( $output ) ) {
						$output .= "\n";
					}
					break;

				case 403:
					$output = sprintf( t('---- Permission denied to "%s"', I18n::_DTD), $input->cli('r') )."\n";
					$zula->setExitCode( 5 );
					break;

				case 404:
					$output = sprintf( t('---- The request path "%s" does not exist', I18n::_DTD), $input->cli('r') )."\n";
					$zula->setExitCode( 4 );
					break;
			}
		}
	}
	Hooks::notifyAll( 'bootstrap_loaded', (isset($output) && $output instanceof Theme),
										  $dispatcher->getStatusCode(),
										  $dispatcher->getDispatchData() );
	return is_bool($output) ? true : print $output;

?>
