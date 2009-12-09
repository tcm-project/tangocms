<?php

/**
 * Zula Framework Validator (Between)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Validator
 */

	class Validator_Between extends Validator_Base {

		/**
		 * Minimum value
		 * @var int
		 */
		protected $min = null;

		/**
		 * Maximum value
		 * @var int
		 */
		protected $max = null;

		/**
		 * Toggles strict checking, if enable then the value
		 * must *not* include the min or max value
		 * @var bool
		 */
		protected $strict = false;

		/**
		 * Constructor
		 * Sets up the values to use
		 *
		 * @param int $min
		 * @param int $max
		 * @param bool $strict
		 * @return object
		 */
		public function __construct( $min, $max, $strict=false ) {
			$this->min = (int) $min;
			$this->max = (int) $max;
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
				$value = count( $value );
			}
			if ( $this->strict === true ) {
				if ( !($value > $this->min && $value < $this->max) ) {
					return '%1$s '.sprintf( t('must be between %s and %s', Locale::_DTD),
											number_format($this->min),
											number_format($this->max)
										  );
				}
			} else {
				if ( !($value >= $this->min && $value <= $this->max) ) {
					return '%1$s '.sprintf( t('must be between %s and %s inclusive', Locale::_DTD),
											number_format($this->min),
											number_format($this->max)
										  );
				}
			}
			return true;
		}

	}

?>
