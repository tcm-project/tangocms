<?php
// $Id: reset.php 2798 2009-11-24 12:15:41Z alexc $

/**
 * Zula Framework Module (Session)
 * --- Allows the users to reset his/her password and sends
 * an email notification to them
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Session
 */

	class Session_controller_reset extends Zula_ControllerBase {

		/**
		 * Displays the form for a user to enter his/her username in
		 * which will then get a reset code emaled to them.
		 *
		 * @return string
		 */
		public function indexSection() {
			$this->setTitle( t('Reset Password') );
			$this->_locale->textDomain( $this->textDomain() );
			// Build form
			$form = new View_form( 'reset/main.html', 'session' );
			$form->addElement( 'session/username', null, t('Username'), new Validator_Length(1, 255) );
			if ( $form->hasInput() && $form->isValid() ) {
				/**
				 * Check users exists, get details and send email
				 */
				try {
					$user = $this->_ugmanager->getUser( $form->getValues('session/username'), false );
					// Generate a reset code that is unique
					$pdoSt = $this->_sql->prepare( 'SELECT COUNT(id) FROM {SQL_PREFIX}users WHERE reset_code = ?' );
					do {
						$resetCode = zula_create_key();
						$pdoSt->execute( array($resetCode) );
					} while( $pdoSt->fetchColumn() >= 1 );
					$pdoSt->closeCursor();
					// Update user account and attempt to send the email
					$this->_ugmanager->editUser( $user['id'], array('reset_code' => $resetCode) );
					$msgView = $this->loadView( 'reset/email.txt' );
					$msgView->assign( array(
											'RESET_CODE'	=> $resetCode,
											'USER'			=> $user,
											));
					$message = new Email_Message( t('Reset Password'), $msgView->getOutput() );
					$message->setTo( $user['email'] );
					$email = new Email;
					$email->send( $message );
					$this->_event->success( t("An email has been sent to the user's email address") );
					return zula_redirect( $this->_router->makeUrl( 'session', 'reset', 'code' ) );
				} catch ( UGManager_UserNoExist $e ) {
					$this->_event->error( t('User does not exist') );
				} catch ( Email_Exception $e ) {
					$this->_event->error( t('An error occurred while sending the email. Please try again later') );
				}
			}
			return $form->getOutput();
		}

		/**
		 * Shows the form to enter the reset code and new password.
		 *
		 * @return string
		 */
		public function codeSection() {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('Enter Reset Code') );
			// Prepare validation of the form
			$form = new View_form( 'reset/reset.html', 'session' );
			$form->addElement( 'session/code', null, 'Reset Code', new Validator_Length( 48, 48 ) );
			$form->addElement( 'session/password', null, 'Password',
								array(new Validator_Length(4, 32), new Validator_Confirm('session/password_confirm', Validator_Confirm::_POST))
							 );
			if ( $form->hasInput() && $form->isValid() ) {
				$fd = $form->getValues( 'session' );
				try {
					$userId = $this->_ugmanager->resetPassword( $fd['code'], $fd['password'] );
					$this->_event->success( t('Your password has been successfully changed') );
					return zula_redirect( $this->_router->makeUrl( 'session' ) );
				} catch ( UGManager_InvalidResetCode $e ) {
					$this->_event->error( t('Invalid password reset code') );
				}
			}
			return $form->getOutput();
		}

	}

?>
