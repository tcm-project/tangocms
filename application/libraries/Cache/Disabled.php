<?php

/**
 * Zula Framework Cache (Disabled)
 * --- Provides, well - nothing. Cache class that does not cache
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Cache
 */

	class Cache_disabled extends Cache_Base {

		/**
		 * Constructor
		 *
		 * @return object
		 */
		public function __construct() {
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
			return true;
		}

		/**
		 * Fetches an item from the cache if it is still valid
		 *
		 * @param string $key
		 * @return mixed
		 */
		public function get( $key ) {
			return false;
		}

		/**
		 * Removes a cached item
		 *
		 * @param string $key
		 * @return bool
		 */
		public function delete( $key ) {
			return true;
		}
		
		/**
		 * Purges all cached items
		 *
		 * @return bool
		 */
		public function purge() {
			Hooks::notifyAll( 'cache_purge', 'disabled' );
			return true;
		}

	}

?>
