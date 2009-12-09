<?php
// $Id: Words.php 2768 2009-11-13 18:12:34Z alexc $

/**
 * Zula Framework Validator (Words)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Validator
 */

	class Validator_words extends Validator_Base {

		/**
		 * Minimum number of words
		 * @var int
		 */
		protected $min = 0;
		
		/**
		 * Maximum number of words
		 * @var int
		 */
		protected $max = 0;
		
		/**
		 * Constructor
		 * Sets the min and max number of words
		 *
		 * @param int $min
		 * @param int $max
		 * @return object
		 */
		public function __construct( $min, $max ) {
			$this->min = abs( $min );
			$this->max = abs( $max );
		}
		
		/**
		 * Runs the needed checks to see if the value is valid. A non
		 * true value will be returned if it failed.
		 *
		 * @param mixed $value
		 * @return bool|string
		 */
		public function validate( $value ) {
			$matches = array();
			$count = preg_match_all( '#\w+#', $value, $matches );
			if ( $count >= $this->min && $count <= $this->max ) {
				return true;
			}
			$langStr = t('%%1$s must be between %1$d and %2$d words, currently %3$d', Locale::_DTD);
			return sprintf( $langStr, $this->min, $this->max, $count );
		}

	}

?>
