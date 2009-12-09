<?php
// $Id: Email.php 2768 2009-11-13 18:12:34Z alexc $

/**
 * Zula Framework Validator (Email)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Validator
 */

	class Validator_email extends Validator_Base {

		/**
		 * Runs the needed checks to see if the value is valid. A non
		 * true value will be returned if it failed.
		 *
		 * @param mixed $value
		 * @return bool|string
		 */
		public function validate( $value ) {
			return filter_var($value, FILTER_VALIDATE_EMAIL) ? true : t('%1$s must be a valid email address', Locale::_DTD);
		}

	}

?>
