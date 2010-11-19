<?php

/**
 * Zula Framework Cache (eAccelerator)
 * --- Provides eAccelerator based caching
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Robert Clipsham
 * @copyright Copyright (C) 2008, 2009, 2010 Robert Clipsham
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Cache
 */

	class Cache_eaccelerator extends Cache_Base {

		/**
		 * Constructor
		 *
		 * @return object
		 */
		public function __construct() {
			if ( !extension_loaded( 'eaccelerator' ) ) {
				throw new Cache_Exception( 'PHP extension "eaccelerator" not loaded' );
			} else if ( !function_exists( 'eaccelerator_put' ) ) {
				throw new Cache_Exception( 'eAccelerator must be configured with "--with-eaccelerator-content-caching"' );
			}
		}

		/**
		 * Adds a new item that is to be cached
		 *
		 * @param string $key
		 * @param mixed $data
		 * @param bool $overwrite
		 * @return bool
		 */
		public function add( $key, $data, $overwrite=false ) {
			if ( $overwrite == false ) {
				if ( eaccelerator_get( $key ) != null ) {
					return false;
				} else {
					return eaccelerator_put( $key, serialize( $data ), $this->ttl() );
				}
			} else {
				return eaccelerator_put( $key, serialize( $data ), $this->ttl() );
			}
		}

		/**
		 * Fetches an item from the cache if it is still valid
		 *
		 * @param string $key
		 * @return mixed
		 */
		public function get( $key ) {
			return unserialize( eaccelerator_get( $key ) );
		}

		/**
		 * Deletes an item out of the cache, or multiple items
		 *
		 * @param string|array $key
		 * @return int
		 */
		public function delete( $key ) {
			return eaccelerator_rm( $key );
		}

		/**
		 * Purges all cached items
		 *
		 * @return bool
		 */
		public function purge() {
			Hooks::notifyAll( 'cache_purge', 'eaccelerator' );
			return true;
		}

	}

?>
