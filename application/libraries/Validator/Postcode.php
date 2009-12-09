<?php
// $Id: Postcode.php 2768 2009-11-13 18:12:34Z alexc $

/**
 * Zula Framework Validator (Postcode)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Validator
 */

	class Validator_postcode extends Validator_Base {

		/**
		 * Extra chars that are valid
		 * @var string
		 */
		protected $allowHalf = false;

		/**
		 * Constructor
		 *
		 * @param bool $allowHalf
		 * @return object
		 */
		public function __construct( $allowHalf=false ) {
			$this->allowHalf = (bool) $allowHalf;
		}

		/**
		 * Runs the needed checks to see if the value is valid. A non
		 * true value will be returned if it failed.
		 *
		 * @param mixed $value
		 * @return bool|string
		 */
		public function validate( $value ) {
			$pattern = '#^[A-Z]{1,2}[0-9R][0-9A-Z]?( [0-9][ABD-HJLNP-UW-Z]{2})'.($this->allowHalf ? '?' : '').'$#i';
			return (preg_match($pattern, $value)) ? true : t('%1$s must be a valid postcode', Locale::_DTD);
		}

	}

?>
