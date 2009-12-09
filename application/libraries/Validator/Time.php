<?php
// $Id: Time.php 2768 2009-11-13 18:12:34Z alexc $

/**
 * Zula Framework Validator (Time)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Validator
 */

	class Validator_time extends Validator_Base {

		/**
		 * Runs the needed checks to see if the value is valid. A non
		 * true value will be returned if it failed.
		 *
		 * @param mixed $value
		 * @return bool|string
		 */
		public function validate( $value ) {
			return (strtotime($value) === false ? t('%1$s must be a valid time', Locale::_DTD) : true);
		}

	}

?>
