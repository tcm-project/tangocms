<?php

/**
 * Zula Framework Cache (Tmp)
 * --- Temporary cache that simply stories data in class property, nothing
 * is really stored
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Cache
 */

	class Cache_tmp extends Cache_Base {

		/**
		 * Local cache for the fetched items, to provide basic caching
		 * @var array
		 */
		protected $cached = array();
		
		/**
		 * Adds a new item that is to be cached
		 *
		 * @param string $key
		 * @param mixed $data
		 * @param bool $overwrite
		 * @return bool
		 */
		public function add( $key, $data, $overwrite=false ) {
			if ( $key === null || array_key_exists( $key, $this->cached ) && $overwrite == false ) {
				return false;
			} else {
				$this->cached[ $key ] = $data;
				return true;
			}
		}

		/**
		 * Fetches an item from the cache if it is still valid
		 *
		 * @param string $key
		 * @return mixed
		 */
		public function get( $key ) {
			if ( $key === null ) {
				return false;
			} else {
				return array_key_exists($key, $this->cached) ? $this->cached[$key] : false;
			}
		}

		/**
		 * Removes a cached item
		 *
		 * @param string $keys
		 * @return int
		 */
		public function delete( $keys ) {
			$delCount = 0;
			foreach( array_filter( (array) $keys ) as $cacheKey ) {
				if ( array_key_exists( $cacheKey, $this->cached ) ) {
					unset( $this->cached[ $cacheKey ] );
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
			$this->cached = array();
			Hooks::notifyAll( 'cache_purge', 'tmp' );
			return true;
		}

	}

?>
