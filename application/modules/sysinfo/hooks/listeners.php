<?php

/**
 * Zula Framework Module (Sysinfo)
 * --- Hooks file for listning to possible events
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Sysinfo
 */

	class Sysinfo_hooks extends Zula_HookBase {

		/**
		 * Latest TCM versions
		 * @var array
		 */
		protected $versions = null;

		/**
		 * Constructor
		 * Calls the parent constructor to register the methods
		 *
		 * Also gathers the version file to get latest versions
		 *
		 * @return object
		 */
		public function __construct() {
			parent::__construct( $this );
			$verFile = $this->_zula->getDir( 'tmp' ).'/sysinfo/versions';
			if ( file_exists( $verFile ) && is_readable( $verFile ) && filemtime( $verFile )+(60*60*8) > time() ) {
				$this->versions = @unserialize( file_get_contents($verFile) );
			} else {
				// Attempt to re-read the versions
				if ( ini_get( 'allow_url_fopen' ) ) {
					$stream = stream_context_create( array(
														'http' => array(
																	'method'	=> 'GET',
																	'header'	=> 'X-TangoCMS-Version: '._PROJECT_VERSION."\r\n".
																				   'X-TangoCMS-USI: '.zula_hash( $_SERVER['HTTP_HOST'] )."\r\n",
																	'timeout'	=> 6,
																	)
														));
					foreach( array('stable', 'unstable') as $type ) {
						$tmpVer = @file_get_contents( 'http://releases.tangocms.org/latest/'.$type, false, $stream );
						if ( isset( $http_response_header[0] ) && strpos( $http_response_header[0], '200' ) !== false ) {
							$this->versions[ $type ] = trim($tmpVer);
						}
					}
					if ( zula_make_dir( $this->_zula->getDir( 'tmp' ).'/sysinfo' ) ) {
						file_put_contents( $verFile, serialize($this->versions) );
					}
				} else {
					$this->_log->message( 'Sysinfo unable to get latest TangoCMS versions, allow_url_fopen disabled', Log::L_NOTICE );
				}
			}
		}

		/**
		 * 'bootstrap_loaded' hook
		 *
		 * @param bool $themeLoaded
		 * @return bool
		 */
		public function hookBootstrapLoaded( $themeLoaded ) {
			if ( $themeLoaded && $this->_router->getSiteType() == 'admin' && is_array( $this->versions ) ) {
				$curVerType = zula_version_type( _PROJECT_VERSION );
				if ( version_compare( _PROJECT_VERSION, $this->versions[ $curVerType ], '<' ) ) {
					$this->_event->success( sprintf( t('A new TangoCMS version (%1$s) is available', _PROJECT_ID.'-sysinfo'),
													 $this->versions[ $curVerType ]
												   ) );
					return true;
				}
			}
			return false;
		}

	}

?>
