<?php

/**
 * Zula Framework Validator (Alpha)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Validator
 */

	class Validator_alpha extends Validator_Base {

		/**
		 * Regex pattern to use
		 * @var string
		 */
		protected $pattern = null;

		/**
		 * Are spaces allowed?
		 * @var bool
		 */
		protected $allowSpaces = true;

		/**
		 * Constructor
		 * Sets whether a space character is allowed
		 *
		 * @param bool $allowSpaces
		 * @return object
		 */
		public function __construct( $allowSpaces=true ) {
			$this->pattern = '#[^A-Z'.($allowSpaces ? ' ' : '').']+#i';
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
			if ( !is_string( $value ) || preg_match( $this->pattern, $value ) ) {
				$msg = t('%1$s must contain only alpha characters ', I18n::_DTD);
				return $msg.($this->allowSpace ? t('(spaces allowed)', I18n::_DTD) : t('only', I18n::_DTD));
			} else {
				return true;
			}
		}

	}

?>
