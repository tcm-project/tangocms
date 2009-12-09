<?php

/**
 * Zula Framework Validator (Url)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Validator
 */

	class Validator_url extends Validator_Base {

		/**
		 * Strict matching? i.e require protocol
		 * @var bool
		 */
		protected $strict = true;

		/**
		 * Constructor
		 *
		 * @param bool $strict
		 * @return object
		 */
		public function __construct( $strict=true ) {
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
			$protocol = '[A-Z][A-Z0-9+.\-]+://';
			if ( !$this->strict ) {
				$protocol = '('.$protocol.')?';
			}
			$regex = '#^'.$protocol.'[A-Z0-9][A-Z0-9.\-]+\.[A-Z0-9.\-]+(:\d+)?(/([^\s]+)?)?$#i';
			return preg_match( $regex, $value ) ? true : t('%1$s must be a valid URL', Locale::_DTD );
		}

	}

?>
