<?php

/**
 * Zula Framework Module
 * Displays details of the server environment and TCM version. Also checks for
 * TCM updates/upgrades
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Sysinfo
 */

	class Sysinfo_controller_index extends Zula_ControllerBase {

		/**
		 * Constructor
		 *
		 * Sets the common page links
		 */
		public function __construct( $moduleDetails, $config, $sector ) {
			parent::__construct( $moduleDetails, $config, $sector );
			$this->setPageLinks( array(
										t('System information')			=> $this->_router->makeUrl( 'sysinfo' ),
										t('Writable files/directories')	=> $this->_router->makeUrl( 'sysinfo', 'index', 'writable' ),
										t('Update checker')				=> $this->_router->makeUrl( 'sysinfo', 'index', 'update' ),
									 ));
			if ( function_exists( 'phpinfo' ) ) {
				$this->setPageLinks( array(t('PHP info') => $this->_router->makeUrl('sysinfo', 'index', 'phpinfo')) );
			}
		}

		/**
		 * Display various information about the server environment
		 * and TCM status
		 *
		 * @return string
		 */
		public function indexSection() {
			$this->setTitle( t('System information') );
			$this->setOutputType( self::_OT_INFORMATIVE );
			// Build view with all the lovely information
			$pdoVersion = $this->_sql->getAttribute( PDO::ATTR_DRIVER_NAME ).' '.$this->_sql->getAttribute( PDO::ATTR_SERVER_VERSION );
			$view = $this->loadView( 'index/info.html' );
			$view->assign( array(
								'TCM_VERSION'	=> _PROJECT_VERSION,
								'ZULA_VERSION'	=> Zula::_VERSION,
								'PHP_VERSION'	=> PHP_VERSION.' ('.PHP_SAPI.')',
								'WEBSERVER'		=> $_SERVER['SERVER_SOFTWARE'],
								'PHP'			=> array(
														'PDO'				=> $pdoVersion,
														'allow_url_fopen'	=> ini_get( 'allow_url_fopen' ),
														'display_errors'	=> ini_get( 'display_errors' ),
														'error_reporting'	=> ini_get( 'error_reporting' ),
														'magic_quotes_gpc'	=> ini_get( 'magic_quotes_gpc' ),
														'register_globals'	=> ini_get( 'register_globals' ),
														),
								));
			return $view->getOutput();
		}

		/**
		 * Checks which directories and files are writable to the user
		 * the script is running as
		 *
		 * @return string
		 */
		public function writableSection() {
			$this->setTitle( t('Writable files/directories') );
			$this->setOutputType( self::_OT_INFORMATIVE );
			// Generate array of all files/dirs
			$items = array();
			$directoryIterator = new RecursiveDirectoryIterator( $this->_zula->getDir( 'config' ) );
			foreach( new RecursiveIteratorIterator( $directoryIterator ) as $file ) {
				if ( substr( $file->getFileName(), 0, 1 ) != '.' && $file->isFile() ) {
					$items['files'][] = array(
											'path'		=> $file->getPathName(),
											'result'	=> zula_is_writable( $file->getPathName() ),
											);
				}
			}
			// Dirs
			$dirs = array(
						$this->_zula->getDir( 'config' ).'/layouts',
						$this->_zula->getDir( 'logs' ),
						$this->_zula->getDir( 'tmp' ),
						$this->_zula->getDir( 'uploads' ),
						$this->_zula->getDir( 'locale' ),
						);
			foreach( $dirs as $dir ) {
				$items['dirs'][] = array('path' => $dir, 'result' => zula_is_writable( $dir ));
			}
			$view = $this->loadView( 'index/writable.html' );
			$view->assign( array(
								'DIRS'	=> $items['dirs'],
								'FILES'	=> $items['files'],
								));
			return $view->getOutput();
		}

		/**
		 * Gets the latest versions of TangoCMS and compare
		 *
		 * @return string
		 */
		public function updateSection() {
			$this->setTitle( t('Update checker') );
			$this->setOutputType( self::_OT_INFORMATIVE );
			// Gather the latest stable and unstable versions
			$versions = array( 'stable' => t('Unknown'), 'unstable' => t('Unknown') );
			if ( ini_get( 'allow_url_fopen' ) ) {
				$stream = stream_context_create( array(
													'http' => array(
																'method'	=> 'GET',
																'header'	=> 'X-TangoCMS-Version: '._PROJECT_VERSION."\r\n".
																			   'X-TangoCMS-USI: '.zula_hash( $_SERVER['HTTP_HOST'] )."\r\n",
																'timeout'	=> 6,
																)
													));
				foreach( $versions as $type=>$ver ) {
					$tmpVer = @file_get_contents( 'http://releases.tangocms.org/latest/'.$type, false, $stream );
					if ( isset( $http_response_header[0] ) && strpos( $http_response_header[0], '200' ) !== false ) {
						$versions[ $type ] = trim($tmpVer);
					}
				}
				file_put_contents( $this->_zula->getDir( 'tmp' ).'/sysinfo/versions', serialize( $versions ) );
			}
			$view = $this->loadView( 'index/update.html' );
			$view->assign( array(
								'TCM_VERSION'	=> _PROJECT_VERSION,
								'VERSION_TYPE'	=> zula_version_type( _PROJECT_VERSION ),
								'LATEST'		=> $versions,
								));
			return $view->getOutput();
		}

		/**
		 * Displays the generic phpinfo() page
		 *
		 * @return bool
		 */
		public function phpinfoSection() {
			if ( function_exists( 'phpinfo' ) ) {
				phpinfo();
				return false;
			} else {
				throw new Module_ControllerNoExist;
			}
		}

	}

?>
