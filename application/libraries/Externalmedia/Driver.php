<?php
// $Id: Driver.php 2823 2009-12-03 15:12:40Z alexc $

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

	abstract class Externalmedia_Driver {

		/**
		 * Holds the fetched attributes of the external media.
		 *
		 * @var array
		 */
		protected $attributes = array();

		/**
		 * Constructor - takes the media id and executes fetchAttributes().
		 *
		 * @param string $mediaId
		 * @return object
		 */
		public function __construct( $mediaId ) {
			$this->attributes['mediaId'] = $mediaId;
			if ( !$this->fetchAttributes() ) {
				throw new ExternalMediaDriver_NoRead( 'Unable to interface with external media API.' );
			}
		}

		/**
		 * Attribute getter - returns value of desired
		 * attributes from call $obj->attribute.
		 *
		 * @param string $attribute
		 * @return string
		 */
		public function __get( $attribute ) {
			if ( array_key_exists( $attribute, $this->attributes ) ) {
				return $this->attributes[ $attribute ];
			}
		}

		/**
		 * Abstract function definition - enforces the
		 * definition of the attribute fetching function
		 * in the derived classes.
		 *
		 * @return bool
		 */
		abstract protected function fetchAttributes();

	}

?>