<?php

/**
 * Zula Framework Cache (Memcached)
 * --- Provides Memcached based caching
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Robert Clipsham
 * @copyright Copyright (C) 2008, Robert Clipsham
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Cache
 */

	class Cache_memcached extends Cache_Base {

		/**
		 * Whether to use compression
		 * @var mixed
		 */
		private $compression = false;

		/**
		 * Constructor
		 *
		 * @return object
		 */
		public function __construct() {
			if ( !extension_loaded( 'memcache' ) ) {
				throw new Exception( 'Memcache extension is currently not loaded, unable to use Memcached caching' );
			}
			if ( extension_loaded( 'zlib' ) ) {
				$this->compression = MEMCACHE_COMPRESSED;
			}
			foreach( $this->_config->get('memcached_servers') as $server ) {
				Memcache::addServer($server);
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
				return Memcache::add( $key, $data, $this->compression, $this->ttl() );
			} else {
				return Memcache::set( $key, $data, $this->compression, $this->ttl() );
			}
		}

		/**
		 * Fetches an item from the cache if it is still valid
		 *
		 * @param string $key
		 * @return mixed
		 */
		public function get( $key ) {
			return $key === null ? false : Memcache::get( $key );
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
				if ( Memcache::delete( $key ) ) {
					++$delCount;
				}
			}
			return $delCount;
		}		

	}

?>
