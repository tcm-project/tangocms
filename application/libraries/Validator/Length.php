<?php

/**
 * Zula Framework Validator (Length)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Validator
 */

	class Validator_length extends Validator_Base {

		/**
		 * Min value it can be
		 * @var int
		 */
		protected $min = 0;

		/**
		 * Max value
		 * var int
		 */
		protected $max = null;

		/**
		 * Constructor
		 *
		 * @param int $min
		 * @param int $max
		 * @return object
		 */
		public function __construct( $min, $max=null ) {
			$this->min = (int) $min;
			$this->max = (int) $max;
		}

		/**
		 * Runs the needed checks to see if the value is valid. A non
		 * true value will be returned if it failed.
		 *
		 * @param mixed $value
		 * @return bool|string
		 */
		public function validate( $value ) {
			$valueLen = is_array($value) ? count($value) : zula_strlen($value);
			if ( $valueLen < $this->min || ($this->max && $valueLen > $this->max) ) {
				return sprintf( t('%%1$s must be between %1$s and %2$s characters long', I18n::_DTD),
							    number_format($this->min),
							    number_format($this->max)
							  );
			} else {
				return true;
			}
		}

	}

?>
