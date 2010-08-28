<?php

/**
 * Zula Framework Antispam Base
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Robert Clipsham
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009, Robert Clipsham
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Antispam
 */

	interface Antispam_Interface {

		/**
		 * Create the necessary form elements, and return a string to embed
		 *
		 * @return string
		 */
		public function create();

		/**
		 * Check if the Antispam method has passed
		 *
		 * @return bool
		 */
		public function check();

	}

?>
