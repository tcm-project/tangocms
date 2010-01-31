<?php

/**
 * Zula Framework index (collector/front controller)
 * --- Sets the mode Zula should run in and includes the main 'index.php'
 * file to avoide un-needed duplicate code.
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Installer
 */

	/**
	 * Set some constants such as path to application folder
	 * as well as the appliaction mode to run in
	 */
	define( '_PATH_APPLICATION', '../application' );
	define( '_APP_MODE', 'installation' );

	define( '_PROJECT_LATEST_VERSION', '2.4.55' );

	// Include the main index.php file
	require_once '../index.php';

?>
