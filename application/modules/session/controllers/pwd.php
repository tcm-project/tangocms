<?php

/**
 * Zula Framework Module
 * --- Allows the user to reset his/her password, or to change it once
 * it has expired.
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Session
 */

	class Session_controller_pwd extends Zula_ControllerBase {

		/**
		 * Displays a form to enter in an email address; if this email address
		 * is associated with a user then an email will be sent to them with
		 * a reset code and details on how to reset their password (change it).
		 *
		 * @return string|bool
		 */
		public function forgotSection() {
			$this->setTitle( t('Forgotten your password?') );
			$form = new View_form( 'pwd/form_forgotten.html', 'session' );
			$form->addElement( 'session/email', null, t('Email'), new Validator_Email );
			if ( $form->hasInput() && $form->isValid() ) {
				/**
				 * Check users exists, get details and send email
				 */
				$pdoSt = $this->_sql->prepare( 'SELECT id FROM {SQL_PREFIX}users WHERE email = ?' );
				$pdoSt->execute( array($form->getValues('session/email')) );
				$uid = $pdoSt->fetchColumn();
				$pdoSt->closeCursor();
				try {
					$user = $this->_ugmanager->getUser( $uid );
					// Generate a reset code that is unique
					$pdoSt = $this->_sql->prepare( 'SELECT COUNT(uid) FROM {SQL_PREFIX}users_meta
													WHERE name = "sessionResetCode" AND value = ?' );
					do {
						$resetCode = zula_create_key();
						$pdoSt->execute( array($resetCode) );
					} while( $pdoSt->fetchColumn() >= 1 );
					$pdoSt->closeCursor();
					// Update user account and attempt to send the email
					$this->_ugmanager->editUser( $user['id'], array('sessionResetCode' => $resetCode) );
					$msgView = $this->loadView( 'pwd/email_forgotten.txt' );
					$msgView->assign( array(
											'code'	=> $resetCode,
											'user'	=> $user,
											));
					$message = new Email_Message( t('Forgotten password'), $msgView->getOutput() );
					$message->setTo( $user['email'] );
					$email = new Email;
					$email->send( $message );
					$this->_event->success( t("An email has been sent to the users email address") );
					return zula_redirect( $this->_router->makeUrl('session') );
				} catch ( Ugmanager_UserNoExist $e ) {
					$this->_event->error( t('The provided email does not exist') );
				} catch ( Email_Exception $e ) {
					$this->_event->error( t('An error occurred while sending the email. Please try again later') );
				}
			}
			return $form->getOutput();
		}

		/**
		 * Takes a provided reset code (rc) and allows the user to
		 * enter a new password for their account.
		 *
		 * @return string|bool
		 */
		public function resetSection() {
			$this->setTitle( t('Reset account password') );
			try {
				$rc = $this->_input->get( 'rc' );
				if ( ($uid = $this->_model()->resetCodeUid($rc)) ) {
					$form = new View_form( 'pwd/form_reset.html', 'session' );
					$form->addElement( 'session/password', null, t('Password'),
										array(new Validator_Length(4, 32), new Validator_Confirm('session/password_confirm', Validator_Confirm::_POST))
									);
					if ( $form->hasInput() && $form->isValid() ) {
						try {
							$details = array(
											'password'			=> $form->getValues('session/password'),
											'sessionResetCode'	=> null
											);
							$this->_ugmanager->editUser( $uid, $details );
							$this->_event->success( t('Your password has been successfully changed') );
							return zula_redirect( $this->_router->makeUrl( 'session' ) );
						} catch ( Ugmanager_UserNoExist $e ) {
							$this->_event->error( t('User does not exist') );
						}
					}
					return $form->getOutput();
				} else {
					$this->_event->error( t('Sorry, the reset code provided does not exist') );
				}
			} catch ( Input_KeyNoExist $e ) {
			}
			return zula_redirect( $this->_router->makeUrl('session', 'pwd', 'forgot') );
		}

		/**
		 * Allows the user to change an expired password, however it can not be
		 * the same as the current password!
		 *
		 * @return string|bool
		 */
		public function expireSection() {
			if ( empty( $_SESSION['mod']['session']['changePw'] ) ) {
				throw new Module_ControllerNoExist;
			}
			$this->setTitle( t('Your password has expired') );
			$form = new View_form( 'pwd/expire.html', 'session' );
			$form->addElement( 'session/password', null, 'Password',
								array(
									new Validator_Length(4, 32),
									new Validator_Confirm('session/password_confirm', Validator_Confirm::_POST),
									array($this, 'validatePreviousPw')
									)
							 );
			if ( $form->hasInput() && $form->isValid() ) {
				$this->_ugmanager->editUser( $this->_session->getUserId(),
											 $form->getValues('session')
											);
				$this->_event->success( t('Your password has been successfully changed') );
				unset( $_SESSION['mod']['session']['changePw'] );
				return zula_redirect( $this->_router->getCurrentUrl() );
			}
			return $form->getOutput();
		}

		/**
		 * Validates the password to ensure it is not the same as the previous
		 *
		 * @param string $value
		 * @return bool|string
		 */
		public function validatePreviousPw( $value ) {
			if ( $this->_session->getUser('password') == zula_hash($value) ) {
				return t('Your new password can not be the same as the previous');
			}
			return true;
		}

	}

?>
