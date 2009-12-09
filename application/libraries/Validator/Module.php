<?php

/**
 * Zula Framework Validator (Module)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Validator
 */

	class Validator_Module extends Validator_Base {

		/**
		 * Check installable modules as well
		 * @var bool
		 */
		protected $checkInstallable = false;

		/**
		 * Constructor
		 *
		 * @param bool $checkInstallable
		 * @return object
		 */
		public function __construct( $checkInstallable=false ) {
			$this->checkInstallable = (bool) $checkInstallable;
		}

		/**
		 * Runs the needed checks to see if the value is valid. A non
		 * true value will be returned if it failed.
		 *
		 * @param mixed $value
		 * @return bool|string
		 */
		public function validate( $value ) {
			if ( Module::exists( $value, $this->checkInstallable ) ) {
				return true;
			}
			return t('%1$s must be a valid module', Locale::_DTD);
		}

	}

?>
