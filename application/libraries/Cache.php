<?php
// $Id: Cache.php 2814 2009-12-01 17:19:18Z alexc $

/**
 * Zula Framework Cache Factory
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Cache
 */

	class Cache {

		/**
		 * Constructor - We don't want an instance of this
		 */
		private function __construct() {
		}

		/**
		 * Factory for setting up the correct class
		 *
		 * @param string $type
		 * @param string $regName
		 * @return object
		 */
		static public function factory( $type='file', $regName='cache' ) {
			$type = 'Cache_'.strtolower( $type );
			if ( !trim( $regName ) ) {
				$regName = 'cache';
			}
			$cache = Registry::get( 'zula' )->loadLib( $type, $regName );
			if ( $cache instanceof Cache_Base ) {
				return $cache;
			} else {
				trigger_error( 'Cache factory could not create cache type. "'.$cache.'" must extend "Cache_Base"', E_USER_ERROR );
				return false;
			}
		}

		/**
		 * Gets all available cache types that can be used with
		 * the current environment
		 *
		 * @return array
		 */
		static public function getAvailable() {
			$types = array();
			foreach( new DirectoryIterator( Registry::get( 'zula' )->getDir( 'libs' ).'/Cache' ) as $file ) {
				if ( !$file->isDot() && substr( $file, 0, 1 ) != '.' ) {
					$extension = null;
					$type = basename( $file, '.php' );
					// Ensure all extensions are there to be used
					switch( $type ) {
						case 'Disabled':
						case 'Tmp':
							break;

						case 'Apc':
							$extension = 'apc';
							break;

						case 'Eaccelerator':
							$extension = 'eaccelerator';
							if ( !function_exists( 'eaccelerator_put' ) ) {
								continue 2;
							}
							break;

						case 'File':
							if ( !zula_is_writable( Registry::get( 'zula' )->getDir( 'tmp' ).'/cache' ) ) {
								continue 2;
							}
							break;

						case 'Memcached':
							$extension = 'memcache';
							break;

						default:
							continue 2;
					}
					if ( !isset( $extension ) || extension_loaded( $extension ) ) {
						$types[] = $type;
					}
				}
			}
			return $types;
		}

	}

?>
