<?php

/**
 * Zula Framework Module (settings)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Settings
 */

	class Settings_controller_update extends Zula_ControllerBase {

		/**
		 * All of the avaialble 'categories'
		 * @var array
		 */
		protected $categories = array( 'general', 'email', 'date', 'security', 'cache', 'editing', 'language' );

		/**
		 * Update the settings based on the post-data provided
		 *
		 * @param string $name
		 * @param array $args
		 * @return string
		 */
		public function __call( $name, $args ) {
			$name = substr( $name, 0, -7 );
			if ( !$this->_acl->check( 'settings_update' ) ) {
				throw new Module_NoPermission;
			} else if ( !in_array( $name, $this->categories ) ) {
				throw new Module_ControllerNoExist;
			} else if ( !$this->_input->checkToken() ) {
				$this->_event->error( Input::csrfMsg() );
				return zula_redirect( $this->_router->makeUrl( 'settings', $name ) );
			}
			$this->setTitle( t('Update Settings') );
			// Update all of the provided settings, or insert if they don't exist
			foreach( $this->_input->post( 'setting' ) as $key=>$val ) {
				if ( strpos( $key, 'cache' ) !== 0 ) {
					if ( substr( $key, 8, 9 ) == 'mail/smtp' && !$this->_acl->check( 'settings_access_smtp' ) ) {
						continue;
					}
					try {
						$this->_config_sql->update( $key, $val );
					} catch ( Config_KeyNoExist $e ) {
						$this->_sql->insert( 'config', array( 'name' => $key, 'value' => $val ) );
					}
				}
			}
			/**
			 * Category specific things to do when updating
			 * the settings or other things (ACL forms etc).
			 */
			switch( $name ) {
				case 'general':
					$this->_cache->delete( 'view_default_tags' );
					break;


				case 'cache':
					// Update the config.ini.php file
					try {
						$this->_config_ini->update( 'cache/type', $this->_input->post( 'setting/cache\/type' ) );
						$this->_config_ini->update( 'cache/ttl', $this->_input->post( 'setting/cache\/ttl' ) );
						$this->_config_ini->update( 'cache/js_aggregate', $this->_input->post( 'setting/cache\/js_aggregate' ) );
						$this->_config_ini->update( 'cache/google_cdn', $this->_input->post( 'setting/cache\/google_cdn' ) );
						$this->_config_ini->writeIni();
						// Clear cache if needbe
						if ( $this->_input->post( 'cache_purge' ) ) {
							$this->_cache->purge();
						}
					} catch ( Exception $e ) {
						$this->_event->error( $e->getMessage() );
						$this->_log->message( $e->getMessage(), Log::L_WARNING );
					}
					break;
				case 'language':
					// Update the config.ini.php file
					try {
						$this->_config_ini->update( 'locale/engine', $this->_input->post( 'setting/locale\/engine' ) );
						$this->_config_ini->update( 'locale/default', $this->_input->post( 'setting/locale\/default' ) );
						$this->_config_ini->writeIni();
					} catch( Exception $e ) {
						$this->_event->error( $e->getMessage() );
						$this->_log->message( $e->getMessage(), Log::L_WARNING );
					}
					// Refresh the language list if needed
					if ( $this->_input->post( 'langlist_refresh' ) ) {
						$this->_cache->delete( 'settings/lang_pkgs' );
					}
					$pkg = $this->_input->post( 'lang_pkg' );
					if ( $pkg == 'none' ) {
						break;
					}
					// Download and install a new locale
					if ( !preg_match( '[a-z]{2}_[A-Z]{2}', $pkg ) ) {
						$this->_event->error( t('Cannot install locale: Invalid locale') );
						break;
					}
					if ( !zula_supports( 'zipExtraction' ) ) {
						$this->_event->error( t('Cannot install locale: zip extension not loaded') );
						break;
					}
					$stream = stream_context_create( array(
										'http' => array(
												'method'	=>	'GET',
												'header'	=>	'X-TangoCMS-Version: '._PROJECT_VERSION."\r\n".
															'X-TangoCMS-USI: '.zula_hash( $_SERVER['HTTP_HOST'] )."\r\n",
												'timeout'	=>	6,
												)
										));
					$version = str_replace( '-', '/', zula_version_map( _PROJECT_VERSION ) );
					$zip = @file_get_contents( 'http://releases.tangocms.org/'.$version.'/i18n/'.$pkg.'.zip', false, $stream );	
					if ( isset( $http_response_header[0] ) && strpos( $http_response_header[0], '200' ) !== false ) {
						$zipFile = $this->_zula->getDir( 'tmp' ) . $pkg . '.zip';
						file_put_contents( $zipFile, $zip );
						$zip = new ZipArchive;
						$opened = $zip->open( $zipFile );
						if ( $opened === true ) {
							$zip->extractTo( $this->_zula->getDir( 'locale' ) );
							$zip->close();
							$this->_event->success( t('Locale successfully installed') );
						} else {
							$this->_event->error( t('Could not install locale: Extracting archive failed') );
						}
						@unlink( $zipFile );
					}
					break;
			}
			$this->_event->success( t('Updated Settings') );
			return zula_redirect( $this->_router->makeUrl( 'settings', $name ) );

		}

	}

?>
