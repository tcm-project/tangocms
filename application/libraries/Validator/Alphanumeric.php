<?php
// $Id: Alphanumeric.php 2768 2009-11-13 18:12:34Z alexc $

/**
 * Zula Framework Validator (Alphanumeric)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Validator
 */

	class Validator_alphanumeric extends Validator_Base {

		/**
		 * Regex pattern to use
		 * @var string
		 */
		protected $pattern = null;

		/**
		 * If spaces are allowed in
		 * @var bool
		 */
		protected $allowSpaces = true;

		/**
		 * Extra chars/regex pattern to use
		 * @var string
		 */
		protected $extra = null;

		/**
		 * Constructor
		 *
		 * @param string $extraChars
		 * @param bool $allowSpaces
		 * @return object
		 */
		public function __construct( $extraChars=null, $allowSpaces=true ) {
			$this->extra = $extraChars;
			$extraChars = preg_quote( $extraChars, '#' );
			if ( version_compare( PHP_VERSION, '5.3', '<' ) ) {
				/**
				 * Due to PHP bug #47229 we must manually esacpe the
				 * hypthen ('-') char, it was fixed for 5.3.0
				 */
				$extraChars = addcslashes( $extraChars, '-' );
			}
			$this->pattern = '#[^A-Z0-9'.($allowSpaces ? ' ' : '').$extraChars.']+#i';
			$this->allowSpaces = (bool) $allowSpaces;
		}

		/**
		 * Runs the needed checks to see if the value is valid. A non
		 * true value will be returned if it failed.
		 *
		 * @param mixed $value
		 * @return bool|string
		 */
		public function validate( $value ) {
			if ( preg_match( $this->pattern, $value ) ) {
				if ( empty( $this->extra ) ) {
					$msg = t('%1$s must be alphanumeric only', Locale::_DTD);
				} else {
					$msg = t('%1$s must be alphanumeric, with additional: ', Locale::_DTD).str_replace( '%', '%%', $this->extra );
				}
				if ( $this->allowSpaces === true ) {
					$msg .= ' '.t('(spaces allowed)', Locale::_DTD);
				}
				return $msg;
			} else {
				return true;
			}
		}

	}

?>
