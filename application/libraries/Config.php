<?php
// $Id: Config.php 2768 2009-11-13 18:12:34Z alexc $

/**
 * Zula Framework Configuration
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Config
 */

	class Config extends Config_base {

		/**
		 * Constructor function
		 * From this you can load a configuration object upon constructor
		 *
		 * @param object $objConfig
		 */
		public function __construct( $objConfig=null ) {
			parent::__construct();
			if ( is_object( $objConfig ) ) {
				$this->load( $objConfig );
			}
		}

		/**
		 * Gets all of the values from a configuration object
		 * All configuration keys are converted to lowercase
		 *
		 * @param object $source
		 * @return bool
		 */
		public function load( $source ) {
			if ( !($source instanceof Config_base) ) {
				throw new Config_InvalidObject( 'unable to load config object, class does not extend "Config_base"' );
			}
			$values = $source->getAll();
			if ( !is_array( $values ) ) {
				throw new Config_InvalidValue( 'unable to load config, value given is not an array' );
			}
			// Load the values
			return $this->setConfigValues( zula_merge_recursive( $this->getAll(), $values ) );
		}

	}

?>
