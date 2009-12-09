<?php

/**
 * Zula Framework Cache (eAccelerator)
 * --- Provides eAccelerator based caching
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Robert Clipsham
 * @copyright Copyright (C) 2008, Robert Clipsham
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
				throw new Exception( 'eAccelerator extension is currently not loaded, unable to use eAccelerator caching' );
			}
			if ( !function_exists( 'eaccelerator_put' ) ) {
				throw new Exception( 'eAccelerator functions required for eAccelerator caching are not enabled. Please reconfigure eAccelerator with --with-eaccelerator-content-caching to enable.' );
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
			if ( $key === null ) {
				return false;
			} else if ( $overwrite == false ) {
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
			return $key === null ? false : unserialize( eaccelerator_get( $key ) );
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
