<?php

/**
 * Zula Framework Logging Interface
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Log
 */

	interface Log_Base {

		/**
		 * Main method that is called on all loggers from
		 * the Log class
		 *
		 * @param string $message
		 * @param int $level
		 * @param string $file
		 * @param int $line
		 * @return bool
		 */
		public function logMessage( $message, $level, $file='unknown', $line=0 );

	}

?>
