<?php

/**
 * Zula Framework Cache (APC)
 * --- Provides APC based caching
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Cache
 */

	class Cache_apc extends Cache_Base {

		/**
		 * Constructor
		 *
		 * @return object
		 */
		public function __construct() {
			if ( !extension_loaded( 'apc' ) ) {
				throw new Cache_Exception( 'PHP extension "apc" not loaded' );
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
				return apc_add( $key, $data, $this->ttl() );
			} else {
				return apc_store( $key, $data, $this->ttl() );
			}
		}

		/**
		 * Fetches an item from the cache if it is still valid
		 *
		 * @param string $key
		 * @return mixed
		 */
		public function get( $key ) {
			return $key === null ? false : apc_fetch( $key );
		}

		/**
		 * Deletes an item out of the cache, or multiple items
		 *
		 * @param string|array $key
		 * @return int
		 */
		public function delete( $key ) {
			$delCount = 0;
			foreach( array_filter( (array) $key ) as $cacheKey ) {
				if ( apc_delete( $cacheKey ) ) {
					++$delCount;
				}
			}
			return $delCount;
		}

		/**
		 * Purges all cached items
		 *
		 * @return bool
		 */
		public function purge() {
			Hooks::notifyAll( 'cache_purge', 'apc' );
			return apc_clear_cache();
		}

	}

?>
