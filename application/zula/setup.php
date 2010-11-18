<?php

/**
 * Zula Framework setup specific
 * Does specific tasks that may be needed, such as adding new config values that previous
 * versions of Zula may not have had (If you are upgrading, for example).
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Install
 */

 	Registry::get( 'cache' )->purge();
 	Registry::unregister( 'cache' );
	Cache::factory( 'tmp' );
	I18n::factory( 'gettext_php' );

	$config->update( 'config/title', sprintf( t('%s %s setup'), _PROJECT_NAME, _PROJECT_LATEST_VERSION ) );
	$config->update( 'acl/enable', 0 );

?>
