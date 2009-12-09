<?php
// $Id: Antispam.php 2823 2009-12-03 15:12:40Z alexc $

/**
 * Zula framework Antispam factory
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Robert Clipsham
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009, Robert Clipsham
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Antispam
 */

	class Antispam extends Zula_LibraryBase {

		/**
		 * The backend object
		 * @var object
		 */
		protected $backend = null;

		/**
		 * Gets all available antispam types that can be used
		 *
		 * @return array
		 */
		static public function getAvailable() {
			$types = array();
			foreach( new DirectoryIterator( Registry::get( 'zula' )->getDir( 'libs' ).'/Antispam' ) as $file ) {
				if ( strpos( $file, '.' ) !== 0 ) {
					$tmpType = pathinfo( $file, PATHINFO_FILENAME );
					if ( !in_array( $tmpType, array('Interface', 'Exceptions') ) ) {
						$types[] = $tmpType;
					}
				}
			}
			return $types;
		}
		
		/**
		 * Constructor
		 * Selects which Antispam backend to use
		 *
		 * @param string $backend
		 * @return object
		 */
		public function __construct( $backend=null ) {
			if ( $backend == null ) {
				try {
					$backend = $this->_config->get( 'antispam/backend' );
				} catch ( Config_KeyNoExist $e ) {
					$backend = 'captcha';
				}
			}
			if ( class_exists( 'Antispam_'.$backend ) ) {
				$backend = 'Antispam_'.$backend;
				$this->backend = new $backend;
				if ( !($this->backend instanceof Antispam_Interface) ) {
					throw new Antispam_InvalidBackend( $backend.' does not extend Antispam_base' );
				}
			} else {
				throw new Antispam_InvalidBackend( $backend );
			}
		}

		/**
		 * Passes all function calls to the correct backend
		 *
		 * @param string $name
		 * @param array $args
		 * @return mixed
		 */
		public function __call( $name, $args ) {
			$callback = array($this->backend, $name);
			if ( is_callable( $callback ) ) {
				try {
					return call_user_func_array( $callback, $args );
				} catch ( Antispam_Exception $e ) {
					$this->_log->message( 'Antispam: '.$e->getMessage(), Log::L_WARNING );
					return false;
				}
			}
		}

	}

?>
