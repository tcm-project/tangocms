<?php

/**
 * Zula Framework Validator (Regex)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Validator
 */

	class Validator_regex extends Validator_Base {

		/**
		 * Pattern to use
		 * @var string
		 */
		protected $pattern = '#^$#';

		/**
		 * Display custom value for pattern, so user doesn't see
		 * cryptic regex in error message returned
		 * @var string
		 */
		protected $patternDisplay = false;

		/**
		 * Constructor
		 * Sets the pattern to use
		 *
		 * @param string $pattern
		 * @param bool|string $patternDisplay
		 * @return object
		 */
		public function __construct( $pattern, $patternDisplay=false ) {
			$this->pattern = $pattern;
			$this->patternDisplay = $patternDisplay ? $patternDisplay : $pattern;
		}

		/**
		 * Runs the needed checks to see if the value is valid. A non
		 * true value will be returned if it failed.
		 *
		 * @param mixed $value
		 * @return bool|string
		 */
		public function validate( $value ) {
			return preg_match( $this->pattern, $value ) ? true
														: sprintf( t('%%1$s value must match %1$s', Locale::_DTD),
																	$this->patternDisplay
																 );
		}

	}

?>
