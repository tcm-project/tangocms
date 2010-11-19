<?php

/**
 * Zula Framework Cache (WinCache)
 * --- Provides WinCache based cashing
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Cache
 */

	class Cache_wincache extends Cache_Base {

		/**
		 * Constructor
		 *
		 * @return object
		 */
		public function __construct() {
			if ( !extension_loaded( 'wincache' ) ) {
				throw new Cache_Exception( 'PHP extension "wincache" not loaded' );
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
				return wincache_ucache_add( $key, $data, $this->ttl() );
			} else {
				return wincache_ucache_set( $key, $data, $this->ttl() );
			}
		}

		/**
		 * Fetches an item from the cache if it is still valid
		 *
		 * @param string $key
		 * @return mixed
		 */
		public function get( $key ) {
			if ( $key !== null ) {
				$value = wincache_ucache_get( $key, $success );
				if ( $success ) {
					return $value;
				}
			}
			return false;
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
				if ( wincache_ucache_delete( $cacheKey ) ) {
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
			Hooks::notifyAll( 'cache_purge', 'wincache' );
			return wincache_ucache_clear();
		}

	}

?>
