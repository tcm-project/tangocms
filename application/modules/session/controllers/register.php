<?php

/**
 * Zula Framework Module (Session)
 * --- Allows the users to register an account and reset password
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Session
 */

	class Session_controller_register extends Zula_ControllerBase {

		/**
		 * Displays and handles the form for new users to register an account
		 *
		 * @return string
		 */
		public function indexSection() {
			$this->setTitle( t('Register an account') );
			// Check that registrations are actually available
			if ( $this->_config->get( 'session/allow_register' ) == false ) {
				throw new Module_ControllerNoExist;
			} else if ( $this->_config->get( 'session/force_https' ) ) {
				$formUrl = $this->_router->makeUrl( 'session', 'register' )->makeFull('&', null, true);
				if ( $this->_router->getScheme() != 'https' ) {
					return zula_redirect( $formUrl );
				}
			} else {
				$formUrl = $this->_router->makeUrl( 'session', 'register' );
			}
			// Build the form and prepare validation
			$form = new View_Form( 'register/form.html', 'session' );
			$form->action( $formUrl )
				 ->antispam(true);
			$form->addElement( 'session/username', null, t('Username'),
								array(new Validator_Alphanumeric('_()!:@.^-'), new Validator_Length(2, 32), array($this, 'validateUsername'))
							 );
			$form->addElement( 'session/password', null, t('Password'),
								array(new Validator_Length(4, 64), new Validator_Confirm('session/password_confirm', Validator_Confirm::_POST))
							 );
			$form->addElement( 'session/email', null, t('Email'),
								array(new Validator_Email, new Validator_Confirm('session/email_confirm', Validator_Confirm::_POST), array($this, 'validateEmail'))
							 );
			$form->addElement( 'session/terms_agree', null, t('Terms'), new Validator_Bool, false );
			if ( $form->hasInput() ) {
				if ( $this->_config->get('session/register_terms') && !$this->_input->has('post', 'session/terms') ) {
					$this->_event->error( t('Please agree to the terms and conditions') );
					$hasTerms = false;
				} else {
					$hasTerms = true;
				}
				if ( $form->isValid() && $hasTerms ) {
					/**
					* Attempt to add the new user and send correct email
					*/
					$fd = $form->getValues( 'session' );
					$userDetails = array(
										'username'		=> $fd['username'],
										'password'		=> $fd['password'],
										'email'			=> $fd['email'],
										'group'			=> $this->_config->get( 'session/register_group' ),
										'activate_code'	=> zula_create_key(),
										);
					$validationMethod = $this->_config->get( 'session/validation_method' );
					switch( $validationMethod ) {
						case 'none':
							$userDetails['activate_code'] = '';
							$eventMsg = t('Successfully registered, you may now login.');
							break;
						case 'admin':
							$eventMsg = t('Successfully registered, an admin will review your registration shortly.');
							break;
						case 'user':
						default:
							$validationMethod = 'user'; # Ensure a known validation method.
							$eventMsg = t('Successfully registered, an email has been sent to confirm your registration.');
					}
					// Add the new user and attempt to send the email.
					$uid = $this->_ugmanager->addUser( $userDetails );
					try {
						$msgView = $this->loadView( 'register/validation_'.$validationMethod.'.txt' );
						$msgView->assign( $userDetails );
						$message = new Email_Message( t('Account Details'), $msgView->getOutput() );
						$message->addTo( $userDetails['email'] );
						$email = new Email;
						$email->send( $message );
						// All done, redirect user
						$this->_event->success( $eventMsg );
						return zula_redirect( $this->_router->makeUrl( 'session' ) );
					} catch ( Email_Exception $e ) {
						$this->_ugmanager->deleteUser( $uid );
						$this->_event->error( t('An error occurred while sending the email. Please try again later') );
						$this->_log->message( 'Unable to send registration email: '.$e->getMessage(), Log::L_WARNING );
					}
				}
			}
			// Add T&Cs then output the form
			$form->assign( array('TERMS' => $this->_config->get('session/register_terms')) );
			return $form->getOutput();
		}

		/**
		 * Validator Callback to check if username already exists
		 *
		 * @param mixed $value
		 * @return mixed
		 */
		public function validateUsername( $value ) {
			return $this->_ugmanager->userExists($value, false) ? t('Username already exists') : true;
		}

		/**
		 * Validator Callback to check if email is taken (and duplicate is not allowed)
		 *
		 * @param mixed $value
		 * @return mixed
		 */
		public function validateEmail( $value ) {
			if ( !$this->_config->get( 'session/duplicate_email' ) && $this->_ugmanager->emailTaken( $value ) ) {
				return t('Email address is already taken by another user');
			} else {
				return true;
			}
		}

		/**
		 * Attempts to activate a users account with the
		 * provided activation code
		 *
		 * @return string
		 */
		public function activateSection() {
			$this->setTitle( t('Activate account') );
			/**
			 * Use the provided activation code
			 */
			try {
				$uid = $this->_ugmanager->activateUser( $this->_router->getArgument('code') );
				$user = $this->_ugmanager->getUser( $uid );
				$this->_event->success( sprintf( t('The account "%s" has now been activated'), $user['username'] ) );
			} catch ( Router_ArgNoExist $e ) {
				$this->_event->error( t('No activation code provided') );
			} catch ( Ugmanager_InvalidActivationCode $e ) {
				$this->_event->error( t('There is no user with that activation code') );
			} catch ( Ugmanager_UserNoExist $e ) {
				$this->_event->error( t('User does not exist') );
			}
			return zula_redirect( $this->_router->makeUrl( 'session' ) );
		}

	}

?>
