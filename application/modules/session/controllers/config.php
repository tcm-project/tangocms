<?php
// $Id: config.php 2798 2009-11-24 12:15:41Z alexc $

/**
 * Zula Framework Module (Session)
 * --- Configuration
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Session
 */

	class Session_controller_config extends Zula_ControllerBase {

		/**
		 * Manages configuration settings for the Session module
		 *
		 * @return string
		 */
		public function indexSection() {
			if ( !$this->_acl->check( 'session_manage_config' ) ) {
				throw new Module_NoPermission;
			}
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('Session Configuration') );			
			$this->setOutputType( self::_OT_CONFIG );
			// Build and output view
			$this->addAsset( 'js/logindest.js' );
			$view = $this->loadView( 'config/config.html' );
			$view->assign( $this->_config->get( 'session' ) );
			$view->assignHtml( array('CSRF' => $this->_input->createToken( true )) );
			return $view->getOutput();
		}

		/**
		 * Updates the session config values
		 *
		 * @return string
		 */
		public function updateSection() {
			if ( !$this->_acl->check( 'session_manage_config' ) ) {
				throw new Module_NoPermission;
			} else if ( !$this->_input->checkToken() ) {
				$this->_event->error( Input::csrfMsg() );
			} else {
				$this->_locale->textDomain( $this->textDomain() );
				$this->setTitle( t('Session Configuration') );
				// Update settings
				foreach( $this->_input->post( 'setting' ) as $key=>$val ) {
					try {
						$setting = substr( $key, 8 );
						$this->_config_sql->update( 'session/'.$key, $val );
					} catch ( Config_KeyNoExist $e ) {
						$this->_sql->insert( 'config', array('name' => 'session/'.$key, 'value' => $val) );
					}
				}
				$this->_event->success( t('Updated Session Configuration') );
			}
			return zula_redirect( $this->_router->makeUrl( 'session', 'config' ) );
		}

	}

?>
