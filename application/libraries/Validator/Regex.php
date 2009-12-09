<?php
// $Id: Regex.php 2822 2009-12-03 11:57:57Z alexc $

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
		 * Constructor
		 * Sets the pattern to use
		 *
		 * @param string $pattern
		 * @return object
		 */
		public function __construct( $pattern ) {
			$this->pattern = $pattern;
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
														: sprintf( t('%%1$s value must match pattern "%1$s"', Locale::_DTD),
																	$this->pattern
																 );
		}

	}

?>
