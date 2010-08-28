<?php

/**
 * Zula Framework Antispam
 * --- Disabled antispam, does nothing.
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Antispam
 */

	class Antispam_Disabled extends Zula_LibraryBase implements Antispam_Interface {

		/**
		 * Create the necessary form elements, and return a string to embed
		 *
		 * @return string
		 */
		public function create() {
			return '';
		}

		/**
		 * Check if the Antispam method has passed
		 *
		 * @return bool
		 */
		public function check() {
			return true;
		}

	}

?>
