<?php

/**
 * Zula Framework base abstract library class
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula
 */

	abstract class Zula_LibraryBase extends Zula_Base  {

		/**
		 * The name used this library has in the registry
		 * @param string
		 */
		private $registryName = false;

		/**
		 * Called when Zula loads a library and is set to
		 * call the _on_load method.
		 *
		 * Sets the name used to store the library in the registry
		 *
		 * @param string $regName
		 * @return bool
		 */
		final public function _onLoad( $regName ) {
			$this->registryName = $regName;
			return true;
		}

		/**
		 * Returns the name that was used to store the library
		 * in the registry
		 *
		 * @return string
		 */
		final public function getRegistryName() {
			return $this->registryName;
		}

	}

?>
