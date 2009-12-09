<?php

/**
 * Zula Framework Validator (Confirm)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Validator
 */

	class Validator_confirm extends Validator_Base {

		/**
		 * Consts to say where the confirm value is from
		 */
		const
			_POST	= 1,
			_GET	= 2;

		/**
		 * Confirm value
		 * @var string
		 */
		protected $confirmer = null;

		/**
		 * Source of the confirmer value
		 * @var int
		 */
		protected $source = 0;
		
		/**
		 * Constructor
		 *
		 * @param string $confirmer
		 * @param int $source
		 * @return object
		 */
		public function __construct( $confirmer, $source=null ) {
			$this->confirmer = $confirmer;
			$this->source = (int) $source;
		}

		/**
		 * Runs the needed checks to see if the value is valid. A non
		 * true value will be returned if it failed.
		 *
		 * @param mixed $value
		 * @return bool|string
		 */
		public function validate( $value ) {
			switch( $this->source ) {
				case self::_GET:
					$this->confirmer = $this->_input->get( $this->confirmer );
					break;

				case self::_POST:
					$this->confirmer = $this->_input->post( $this->confirmer );
					break;

			}
			return ($value == $this->confirmer) ? true : t('%1$s value must be the same as the confirm value', Locale::_DTD);
		}

	}

?>
