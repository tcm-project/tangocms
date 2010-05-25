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

	define( '_PROJECT_LATEST_VERSION', '2.5.54' );

	$state = 'installation';
	require '../index.php';

?>
