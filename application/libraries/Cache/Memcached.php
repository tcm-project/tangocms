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
		 * Memcached object
		 * @var object
		 */
		protected $memcached = null;

		/**
		 * Constructor
		 *
		 * @return object
		 */
		public function __construct() {
			if ( !extension_loaded( 'memcached' ) ) {
				throw new Cache_Exception( 'PHP extension "memcached" not loaded' );
			}
			try {
				$servers = explode( ',', $this->_config->get('cache/memcached_servers') );
			} catch ( Config_KeyNoExist $e ) {
				$servers = array();
			}
			if ( empty( $servers ) ) {
				$servers = array('localhost:11211');
			}
			// Configure memcached
			$this->memcached = new Memcached;
			foreach( $servers as $server ) {
				$split = explode( ':', $server );
				if ( !isset( $split[1] ) ) {
					$split[1] = 11211;
				}
				$this->memcached->addServer( $split[0], (int) $split[1] );
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
				return $this->memcached->add( $key, $data, $this->ttl() );
			} else {
				return $this->memcached->set( $key, $data, $this->ttl() );
			}
		}

		/**
		 * Fetches an item from the cache if it is still valid
		 *
		 * @param string $key
		 * @return mixed
		 */
		public function get( $key ) {
			return $this->memcached->get( $key );
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
				if ( $this->memcached->delete( $key ) ) {
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
			return $this->memcached->flush();
		}

	}

?>
