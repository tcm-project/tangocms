<?php

/**
 * Zula Framework ExternalMedia
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author James Stephenson
 * @author Robert Clipsham
 * @copyright Copyright (C) 2007, 2008, 2009 James Stephenson
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Externalmedia
 */

	class Externalmedia {

		/**
		 * Private constructor - shouldn't be able to
		 * instantiate a factory class.
		 */
		private function __construct() {}

		/**
		 * Returns an instance of the requested external
		 * media driver.
		 *
		 * @param string $service
		 * @param string $mediaId
		 * @return object
		 */
		static public function factory( $driver, $mediaId ) {
			$driver = 'Externalmedia_'.zula_camelise( $driver );
			if ( class_exists( $driver ) ) {
				try {
					return new $driver( $mediaId );
				} catch ( ExternalMediaDriver_NoRead $e ) {
					throw new ExternalMedia_DriverError( $e->getMessage() );
				}
			} else {
				throw new ExternalMedia_NoDriver( 'External driver "'.$driver.'" does not exist' );
			}
		}

	}

?>
