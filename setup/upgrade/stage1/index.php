<?php

/**
 * Zula Framework Fall back installation stage
 * This will only ever appear if you are using the SEF router and there is a problem
 * with your .htaccess file or other rewrite rules
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula
 */

	$host = rtrim( $_SERVER['HTTP_HOST'], '/' );
	if ( strpos( $host, 'http://' ) !== 0 && strpos( $host, 'https://' ) !== 0 ) {
		$host = 'http://'.$host;
	}
	$path = substr( dirname($_SERVER['SCRIPT_NAME']), 0, -15 ).'/index.php?url=upgrade/stage1&ns';

	header( 'HTTP/1.1 303 See Other', true, 303 );
	header( 'Location: '.$host.$path );

?>
