<?php

/**
 * Zula Framework Module (contact)
 * --- Displays and handles the sending of contact forms.
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Contact
 */

	class Contact_controller_index extends Zula_ControllerBase {

		/**
		 * Magic method, allows for shorter URLs. Display the specified
		 * contact form to the user.
		 *
		 * @param string $name
		 * @param array $args
		 * @return mixed
		 */
		public function __call( $name, $args ) {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('Contact') );
			$this->setOutputType( self::_OT_COLLECTIVE );
			// Get the correct form ID to use
			$fid = substr( $name, 0, -7 );
			if ( $fid == 'index' ) {
				$fid = null;
				if ( $this->inSector( 'SC' ) && $this->_config->has( 'contact/display_form' ) ) {
					$fid = abs( $this->_config->get( 'contact/display_form' ) );
				}
			}
			if ( $fid == null ) {
				// Get the latest contact form details
				$allForms = $this->_model()->getAllForms(1);
				if ( empty( $allForms) ) {
					return '<p>'.t('No contact forms to display.').'</p>';
				} else {
					$details = array_shift( $allForms );
				}
			} else {
				try {
					$details = $this->_model()->getForm( $fid );
				} catch ( Contact_NoExist $e ) {
					throw new Module_ControllerNoExist;
				}
			}
			$this->setTitle( $details['name'] );
			// Check user has permission to contact form
			$resource = 'contact-form-'.$details['id'];
			if ( !$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource ) ) {
				throw new Module_NoPermission;
			} else if ( $details['email'] == 'tangocms@example.com' ) {
				$this->_event->error( t('The email address set for this form is still the default!') );
			}
			/**
			 * Begin adding validation of all the form fields
			 */
			$form = new View_form( 'contact.html', 'contact' );
			$form->caseSensitive();
			$form->action( $this->_router->makeUrl( 'contact', 'index', $details['id'] ) )
				 ->antispam( true );
			$form->addElement( 'contact/email', $this->_session->getUser('email'), t('Email Address'), new Validator_Email );
			$fields = array();
			foreach( $this->_model()->getFormFields( $details['id'] ) as $tmpField ) {
				$tmpField['options'] = zula_split_config( $tmpField['options'] );
				$fields[ $tmpField['id'] ] = array(
												'id'		=> $tmpField['id'],
												'name'		=> $tmpField['name'],
												'type'		=> $tmpField['type'],
												'options'	=> $tmpField['options'],
												'required'	=> (bool) $tmpField['required'],
												);
				// Use correct validation for the given types.
				switch( $tmpField['type'] ) {
					case 'textbox':
					case 'password':
						$validator = new Validator_Length(1, 300);
						break;

					case 'textarea':
						$validator = new Validator_Length(1, 3000);
						break;

					case 'radio':
					case 'select':
					case 'checkbox':
						$validator = new Validator_InArray( array_values($tmpField['options']) );
						break;

					default:
						$validator = null;
				}
				$form->addElement( 'contact/fields/'.$tmpField['id'], null, $tmpField['name'], $validator, (bool) $tmpField['required'] );
			}
			if ( $form->hasInput() && $form->isValid() ) {
				/**
				 * Build the email to send out to the contact form address
				 */
				$mailBody = $this->loadView( 'email_body.txt' );
				$mailBody->assign( array(
										'form'		=> $details,
										'fields'	=> $fields,
										));
				$mailBody->assignHtml( array('contact' => $form->getValues('contact')) );
				try {
					$message = new Email_message( sprintf( t('Contact Form "%s"'), $details['name'] ), $mailBody->getOutput(), 'text/plain' );
					$message->setTo( $details['email'] );
					$message->setReplyTo( $form->getValues( 'contact/email' ) );
					$email = new Email;
					$email->send( $message );
					$this->_event->success( t('Contact form sent successfully') );
				} catch ( Email_Exception $e ) {
					$this->_event->error( t('Sorry, there was a technical error while sending the contact form') );
					$this->_log->message( $e->getMessage(), Log::L_WARNING );
				}
				return zula_redirect( $this->_router->makeUrl( 'contact', 'index', $details['id'] ) );
			}
			$form->assign( array(
								'form'		=> $details,
								'fields'	=> $fields,
								));
			return $form->getOutput();
		}

	}

?>
