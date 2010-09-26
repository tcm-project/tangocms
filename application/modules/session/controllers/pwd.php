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
		 * Displays the form for a user to enter his/her username in
		 * which will then get a reset code emailed to the address
		 * set within the profile.
		 *
		 * @return string|bool
		 */
		public function resetSection() {
			$this->setTitle( t('Reset password') );
			// Build form
			$form = new View_form( 'pwd/username.html', 'session' );
			$form->addElement( 'session/username', null, t('Username'), new Validator_Length(0, 255) );
			if ( $form->hasInput() && $form->isValid() ) {
				/**
				 * Check users exists, get details and send email
				 */
				try {
					$user = $this->_ugmanager->getUser( $form->getValues('session/username'), false );
					// Generate a reset code that is unique
					$pdoSt = $this->_sql->prepare( 'SELECT COUNT(uid) FROM {SQL_PREFIX}users_meta
													WHERE name = "reset_code" AND value = ?' );
					do {
						$resetCode = zula_create_key();
						$pdoSt->execute( array($resetCode) );
					} while( $pdoSt->fetchColumn() >= 1 );
					$pdoSt->closeCursor();
					// Update user account and attempt to send the email
					$this->_ugmanager->editUser( $user['id'], array('reset_code' => $resetCode) );
					$msgView = $this->loadView( 'pwd/reset_email.txt' );
					$msgView->assign( array(
											'RESET_CODE'	=> $resetCode,
											'USER'			=> $user,
											));
					$message = new Email_Message( t('Reset password'), $msgView->getOutput() );
					$message->setTo( $user['email'] );
					$email = new Email;
					$email->send( $message );
					$this->_event->success( t("An email has been sent to the user's email address") );
					return zula_redirect( $this->_router->makeUrl( 'session', 'pwd', 'code' ) );
				} catch ( Ugmanager_UserNoExist $e ) {
					$this->_event->error( t('The provided username does not exist') );
				} catch ( Email_Exception $e ) {
					$this->_event->error( t('An error occurred while sending the email. Please try again later') );
				}
			}
			return $form->getOutput();
		}

		/**
		 * Shows the form to enter the reset code and new password.
		 *
		 * @return string|bool
		 */
		public function codeSection() {
			$this->setTitle( t('Enter reset code') );
			// Prepare validation of the form
			$form = new View_form( 'pwd/reset.html', 'session' );
			$form->addElement( 'session/code', null, 'Reset Code', new Validator_Length( 48, 48 ) );
			$form->addElement( 'session/password', null, 'Password',
								array(new Validator_Length(4, 32), new Validator_Confirm('session/password_confirm', Validator_Confirm::_POST))
							 );
			if ( $form->hasInput() && $form->isValid() ) {
				$fd = $form->getValues( 'session' );
				try {
					$userId = $this->_model()->resetPassword( $fd['code'], $fd['password'] );
					$this->_event->success( t('Your password has been successfully changed') );
					return zula_redirect( $this->_router->makeUrl( 'session' ) );
				} catch ( Session_InvalidResetCode $e ) {
					$this->_event->error( t('Invalid password reset code') );
				}
			}
			return $form->getOutput();
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
