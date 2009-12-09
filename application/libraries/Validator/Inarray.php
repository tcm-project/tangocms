<?php

/**
 * Zula Framework Validator (InArray)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Validator
 */

	class Validator_inarray extends Validator_Base {

		/**
		 * Array to test against
		 * @var array
		 */
		protected $testArray = array();

		/**
		 * Use strict checking
		 * @var bool
		 */
		protected $strict = false;

		/**
		 * Constructor
		 * Set the array to be used
		 *
		 * @param array $array
		 * @param bool $strict
		 * @return object
		 */
		public function __construct( array $array, $strict=false ) {
			$this->testArray = $array;
			$this->strict = (bool) $strict;
		}

		/**
		 * Runs the needed checks to see if the value is valid. A non
		 * true value will be returned if it failed.
		 *
		 * @param mixed $value
		 * @return bool|string
		 */
		public function validate( $value ) {
			if ( is_array( $value ) ) {
				// Check all values
				$valid = true;
				foreach( $value as $val ) {
					$valid = in_array( $val, $this->testArray, $this->strict );
				}
				if ( $valid ) {
					return true;
				}
			} else if ( in_array( $value, $this->testArray, $this->strict ) ) {
				return true;
			}
			return t('Value of %1$s is not an acceptable value', Locale::_DTD);
		}

	}

?>
