<?php

/**
 * Zula Framework Module (Session)
 * --- Configuration
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Session
 */

	class Session_controller_config extends Zula_ControllerBase {

		/**
		 * Constructor
		 *
		 * @return object
		 */
		public function __construct( $moduleDetails, $config, $sector ) {
			parent::__construct( $moduleDetails, $config, $sector );
			$this->setPageLinks( array(
										t('Configuration')		=> $this->_router->makeUrl( 'session', 'config' ),
										t('Manage Validations')	=> $this->_router->makeUrl( 'session', 'config', 'validations' ),
										));
		}

		/**
		 * Manages configuration settings for the Session module
		 *
		 * @return string
		 */
		public function indexSection() {
			if ( !$this->_acl->check( 'session_manage' ) ) {
				throw new Module_NoPermission;
			}
			$this->_i18n->textDomain( $this->textDomain() );
			$this->setTitle( t('Session Configuration') );
			$this->setOutputType( self::_OT_CONFIG );
			// Check for input data or display the view file
			if ( $this->_input->has( 'post', 'session' ) ) {
				if ( !$this->_input->checkToken() ) {
					$this->_event->error( Input::csrfMsg() );
				} else {
					foreach( $this->_input->post('session') as $key=>$val ) {
						try {
							$this->_config_sql->update( 'session/'.$key, $val );
						} catch ( Config_KeyNoExist $e ) {
							$this->_config_sql->add( 'session/'.$key, $val );
						}
					}
					$this->_event->success( t('Updated session configuration') );
				}
				return zula_redirect( $this->_router->makeUrl('session', 'config') );
			} else {
				$this->addAsset( 'js/logindest.js' );
				$view = $this->loadView( 'config/config.html' );
				$view->assign( $this->_config->get( 'session' ) );
				$view->assignHtml( array('CSRF' => $this->_input->createToken( true )) );
				return $view->getOutput();
			}
		}

		/**
		 * Displays all users awaiting validation, these can either be accepted
		 * or declined.
		 *
		 * @return string
		 */
		public function validationsSection() {
			$this->_i18n->textDomain( $this->textDomain() );
			$this->setTitle( t('Manage Validations') );
			$this->setOutputType( self::_OT_CONFIG );
			if ( !$this->_acl->check( 'session_manage' ) ) {
				throw new Module_NoPermission;
			}
			// Build form validation
			$form = new View_form( 'config/validation.html', 'session' );
			$form->addElement( 'session/action', null, t('Action'), new Validator_InArray( array('accept', 'decline') ) );
			$form->addElement( 'session/uids', null, t('Users'), new Validator_Is('array') );
			if ( $form->hasInput() && $form->isValid() ) {
				// Activate or Decline/Remove all selected users
				foreach( $form->getValues( 'session/uids' ) as $user ) {
					try {
						$user = $this->_ugmanager->getUser( $user );
						if ( $user['activate_code'] ) {
							if ( $form->getValues( 'session/action' ) == 'accept' ) {
								$this->_ugmanager->activateUser( $user['activate_code'] );
								$viewFile = 'config/validation_accepted.txt';
								$eventMsg = t('Selected users are now active');
							} else {
								$this->_ugmanager->deleteUser( $user['id'] );
								$viewFile = 'config/validation_declined.txt';
								$eventMsg = t('Selected users have been declined');
							}
							$msgView = $this->loadView( $viewFile );
							$msgView->assign( array('USERNAME' => $user['username']) );
							// Send off the correct email to the user, to notify them.
							$message = new Email_Message( t('Account Status'), $msgView->getOutput() );
							$message->setTo( $user['email'] );
							$email = new Email;
							$email->send( $message );
						}
					} catch ( Ugmanager_UserNoExist $e ) {
						// We don't really care if it does not exist, do nothing.
					} catch ( Email_Exception $e ) {
						$this->_event->error( t('An error occurred when sending the validation email') );
						$this->_log->message( 'Unable to send validation email: '.$e->getMessage(), Log::L_WARNING );
					}
				}
				$this->_event->success( $eventMsg );
				return zula_redirect( $this->_router->makeUrl('session', 'config', 'validations') );
			}
			$form->assign( array('VALIDATIONS' => $this->_ugmanager->awaitingValidation()) );
			return $form->getOutput();
		}

	}

?>
