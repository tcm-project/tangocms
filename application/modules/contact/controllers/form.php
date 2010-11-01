<?php

/**
 * Zula Framework Module
 * --- Displays and handles the sending of contact forms.
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Contact
 */

	class Contact_controller_form extends Zula_ControllerBase {

		/**
		 * Magic method, allows for shorter URLs. Display the specified
		 * contact form to the user.
		 *
		 * @param string $name
		 * @param array $args
		 * @return mixed
		 */
		public function __call( $name, array $args ) {
			$this->setTitle( t('Contact') );
			$this->setOutputType( self::_OT_COLLECTIVE );
			// Get the correct form identifier to display
			try {
				$contact = $this->_model()->getForm( substr($name, 0, -7), false );
			} catch ( Contact_NoExist $e ) {
				throw new Module_ControllerNoExist;
			}
			if ( !$this->_acl->check( 'contact-form-'.$contact['id'] ) ) {
				throw new Module_NoPermission;
			}
			$this->setTitle( $contact['name'] );
			/**
			 * Prepare form validation, if needed
			 */
			$fields = $this->_model()->getFormFields( $contact['id'] );
			if ( $fields ) {
				$form = new View_form( 'contact.html', 'contact' );
				$form->caseSensitive()
					  ->antispam( true );
				$form->addElement( 'contact/email', $this->_session->getUser('email'), t('Email address'), new Validator_Email );
				foreach( $fields as &$tmpField ) {
					$tmpField['options'] = zula_split_config( $tmpField['options'] );
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
					 * Send out the contact form email
					 */
					$mailBody = $this->loadView( 'email_body.txt' );
					$mailBody->assign( array(
											'form'		=> $contact,
											'fields'	=> $fields,
											'email'		=> $form->getValues( 'contact/email' ),
											));
					$mailBody->assignHtml( array('contact' => $form->getValues('contact')) );
					try {
						$message = new Email_message( sprintf( t('Contact form "%s"'), $contact['name'] ),
													  $mailBody->getOutput(),
													  'text/plain' );
						$message->setTo( $contact['email'] );
						$message->setReplyTo( $form->getValues( 'contact/email' ) );
						$email = new Email;
						$email->send( $message );
						$this->_event->success( t('Contact form sent successfully') );
					} catch ( Email_Exception $e ) {
						$this->_event->error( t('Sorry, there was a technical error while sending the contact form') );
						$this->_log->message( $e->getMessage(), Log::L_WARNING );
					}
					return zula_redirect( $this->_router->getParsedUrl() );
				}
			} else {
				$form = $this->loadView( 'contact.html' );
			}
			$form->assign( array(
								'contact'	=> $contact,
								'fields'	=> $fields,
								));
			return $form->getOutput();
		}

	}

?>
