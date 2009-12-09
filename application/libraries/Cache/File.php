<?php

/**
 * Zula Framework Cache (File)
 * --- Provides simple file-based caching
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Cache
 */

	class Cache_file extends Cache_Base {

		/**
		 * Cache for the fetched items, a cache for the cache library ^^
		 * @var array
		 */
		protected $cached = array();

		/**
		 * Directory used to store caching files
		 * @var string
		 */
		protected $cacheDir = '';

		/**
		 * Constructor
		 * Makes sure the cache directory is set
		 *
		 * @return object
		 */
		public function __construct() {
			$this->cacheDir = $this->_zula->getDir( 'tmp' ).'/cache';
			if ( !is_dir( $this->cacheDir ) ) {
				zula_make_dir( $this->cacheDir );
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
			} else {
				$file = $this->cacheDir.'/'.zula_hash( $key, null, 'md5' );
				if ( file_exists( $file ) && $overwrite == false ) {
					return false;
				} else if ( !zula_is_writable( $this->cacheDir ) ) {
					$this->_log->message( 'CacheFile::add() cache directory "'.$this->cacheDir.'" is not writeable', Log::L_WARNING );
					return false;
				} else {
					$this->cached[ $key ] = $data;
					return file_put_contents( $file, serialize($data) );
				}
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
			} else if ( array_key_exists( $key, $this->cached ) ) {
				return $this->cached[ $key ];
			} else {
				$file = $this->cacheDir.'/'.zula_hash( $key, null, 'md5' );
				if ( file_exists( $file ) ) {
					$mTime = filemtime( $file );
					if ( $mTime == false || ($mTime + $this->ttl()) < time() ) {
						// Cache file has expired, remove it
						$this->delete( $key );
						return false;
					}
					$this->cached[ $key ] = unserialize( file_get_contents( $file ) );
					return $this->cached[ $key ];
				}
			}
			return false;
		}

		/**
		 * Deletes a cached item, or array of items
		 *
		 * @param string|array $key
		 * @return int
		 */
		public function delete( $key ) {
			$delCount = 0;
			foreach( array_filter( (array) $key ) as $cacheKey ) {
				$file = $this->cacheDir.'/'.zula_hash( $cacheKey, null, 'md5' );
				if ( file_exists( $file ) && unlink( $file ) ) {
					unset( $this->cached[ $cacheKey ] );
					++$delCount;
				}
			}
			return $delCount;
		}

		/**
		 * Purge all cache files
		 *
		 * @return bool
		 */
		public function purge() {
			try {
				foreach( new DirectoryIterator( $this->cacheDir ) as $file ) {
					if ( !$file->isDot() && strpos( $file->getFilename(), '.' ) !== 0 ) {
						unlink( $file->getPathname() );
					}
				}
				Hooks::notifyAll( 'cache_purge', 'file' );
				return true;
			} catch ( Exception $e ) {
				return false;
			}
		}

	}

?>
