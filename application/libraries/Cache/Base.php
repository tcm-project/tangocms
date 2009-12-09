<?php
// $Id: Base.php 2768 2009-11-13 18:12:34Z alexc $

/**
 * Zula Framework Cache Base
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Cache
 */

	abstract class Cache_base extends Zula_LibraryBase {

		/**
		 * Time To Live
		 * @var int
		 */
		protected $ttl = 86400;

		/**
		 * Constructor
		 */
		public function __construct() {
		}

		/**
		 * Either gets the current TTL value, however if passed
		 * a value it will set the TTL to it.
		 *
		 * @param int $ttl
		 * @return bool
		 */
		public function ttl( $ttl=null ) {
			if ( is_int( $ttl ) ) {
				$this->ttl = $ttl;
				return true;
			} else {
				return $this->ttl;
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
		abstract public function add( $key, $data, $overwrite=false );

		/**
		 * Fetches an item from the cache if it is still valid
		 *
		 * @param string $key
		 * @return mixed
		 */
		abstract public function get( $key );

		/**
		 * Deletes an item out of the cache, or multiple items
		 *
		 * @param string|array $key
		 * @return int
		 */
		abstract public function delete( $key );

		/**
		 * Purges all cached items
		 *
		 * @return bool
		 */
		public function purge() {
			Hooks::notifyAll( 'cache_purge', null );
			return true;
		}

	}

?>
