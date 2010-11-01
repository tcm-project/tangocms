<?php

/**
 * Zula Framework Module
 * Configure the different contact forms and fields attached.
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Contact
 */

	class Contact_controller_config extends Zula_ControllerBase {

		/**
		 * Amount of forms to display per page
		 */
		const _PER_PAGE = 12;

		/**
		 * Constructor function - Sets the common page links
		 *
		 * @return void
		 */
		public function __construct( $moduleDetails, $config, $sector ) {
			parent::__construct( $moduleDetails, $config, $sector );
			$this->setPageLinks( array(
										t('Manage contact forms')	=> $this->_router->makeUrl( 'contact', 'config' ),
										t('Add contact form')		=> $this->_router->makeUrl( 'contact', 'config', 'add' ),
										));
		}

		/**
		 * Displays all of the contact forms, and can also delete the selected
		 * contact forms.
		 *
		 * @return string|bool
		 */
		public function indexSection() {
			$this->setTitle( t('Manage contact forms') );
			$this->setOutputType( self::_OT_CONFIG );
			if ( $this->_input->checkToken() ) {
				if ( !$this->_acl->check( 'contact_delete' ) ) {
					throw new Module_NoPermission;
				}
				try {
					$delCount = 0;
					foreach( $this->_input->post( 'contact_ids' ) as $fid ) {
						try {
							$form = $this->_model()->getForm( $fid );
							// Check user has permission
							$resource = 'contact-form-'.$form['id'];
							if ( $this->_acl->resourceExists( $resource ) && $this->_acl->check( $resource ) ) {
								$this->_model()->deleteForm( $form['id'] );
								++$delCount;
							}
						} catch ( Contact_NoExist $e ) {
						}
					}
					if ( $delCount ) {
						$this->_event->success( t('Deleted selected contact forms') );
					}
				} catch ( Input_KeyNoExist $e ) {
					$this->_event->error( t('No contact forms selected') );
				}
				return zula_redirect( $this->_router->makeUrl( 'contact', 'config' ) );
			} else if ( $this->_acl->checkMulti( array('contact_edit', 'contact_add', 'contact_delete') ) ) {
				// Find out what page we are on
				try {
					$curPage = abs( $this->_input->get('page')-1 );
				} catch ( Input_KeyNoExist $e ) {
					$curPage = 0;
				}
				$forms = $this->_model()->getAllForms( self::_PER_PAGE, ($curPage*self::_PER_PAGE) );
				$formCount = $this->_model()->getCount();
				if ( $formCount > 0 ) {
					$pagination = new Pagination( $formCount, self::_PER_PAGE );
				}
				$view = $this->loadView( 'config/overview.html' );
				$view->assign( array(
									'FORMS' => $forms,
									'COUNT'	=> $formCount,
									));
				$view->assignHtml( array(
										'PAGINATION'	=> isset($pagination) ? $pagination->build() : '',
										'CSRF'			=> $this->_input->createToken( true ),
										));
				return $view->getOutput();
			} else {
				throw new Module_NoPermission;
			}
		}

		/**
		 * Add a new contact form
		 *
		 * @return string|bool
		 */
		public function addSection() {
			if ( !$this->_acl->check( 'contact_add' ) ) {
				throw new Module_NoPermission;
			}
			$this->setTitle( t('Add contact form') );
			$this->setOutputType( self::_OT_CONFIG );
			// Build and check form
			$form = $this->buildFormView();
			if ( $form->hasInput() && $form->isValid() ) {
				$fd = $form->getValues( 'contact' );
				$details = $this->_model()->addForm( $fd['name'], $fd['email'], $fd['body'] );
				// Update ACL resource
				try {
					$roles = $this->_input->post( 'acl_resources/contact-form' );
				} catch ( Input_KeyNoExist $e ) {
					$roles = array();
				}
				$this->_acl->allowOnly( 'contact-form-'.$details['id'], $roles );
				$this->_event->success( t('Added contact form') );
				return zula_redirect( $this->_router->makeUrl( 'contact', 'config', 'edit', null, array('id' => $details['id']) ) );
			}
			return $form->getOutput();
		}

		/**
		 * Edits an existing contact form
		 *
		 * @return string
		 */
		public function editSection() {
			if ( !$this->_acl->check( 'contact_edit' ) ) {
				throw new Module_NoPermission;
			}
			$this->setTitle( t('Edit contact form') );
			$this->setOutputType( self::_OT_CONFIG );
			try {
				$details = $this->_model()->getForm( $this->_router->getArgument('id') );
				// Check user has permission to the form
				$resource = 'contact-form-'.$details['id'];
				if ( !$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource ) ) {
					throw new Module_NoPermission;
				}
				$this->setTitle( sprintf( t('Edit contact form "%s"'), $details['name'] ) );
				// Prepare form and attempt to update the form
				$form = $this->buildFormView( $details['name'], $details['email'], $details['body'], $details['id'] );
				if ( $form->hasInput() && $form->isValid() ) {
					$fd = $form->getValues( 'contact' );
					$this->_model()->editForm( $details['id'], $fd['name'], $fd['email'], $fd['body'] );
					// Update ACL resource
					try {
						$roles = $this->_input->post( 'acl_resources/contact-form-'.$details['id'] );
					} catch ( Input_KeyNoExist $e ) {
						$roles = array();
					}
					$this->_acl->allowOnly( 'contact-form-'.$details['id'], $roles );
					$this->_event->success( t('Edited contact form') );
				} else {
					return $form->getOutput();
				}
			} catch ( Router_ArgNoExist $e ) {
				$this->_event->error( t('No contact form selected') );
			} catch ( Contact_NoExist $e ) {
				$this->_event->error( t('Contact form does not exist') );
			}
			return zula_redirect( $this->_router->makeUrl('contact', 'config') );
		}

		/**
		 * Builds the form view for adding or editing a contact form
		 *
		 * @param string $name
		 * @param string $email
		 * @param string $body
		 * @param int $id
		 * @return string
		 */
		protected function buildFormView( $name=null, $email=null, $body=null, $id=null ) {
			if ( is_null( $id ) ) {
				$op = 'add';
				$resource = 'contact-form';
			} else {
				$op = 'edit';
				$resource = 'contact-form-'.$id;
				// Add JS for DnD ordering
				$this->_theme->addJsFile( 'jQuery/plugins/dnd.js' );
				$this->addAsset( 'js/dnd_order.js' );
			}
			$viewForm = new View_form( 'config/form_view.html', 'contact', is_null($id) );
			$viewForm->action( $this->_router->makeUrl( 'contact', 'config', $op, null, array('id' => $id) ) );
			$viewForm->addElement( 'contact/name', $name, t('Name'), new Validator_Length(1, 255) );
			$viewForm->addElement( 'contact/email', $email, t('Email'), new Validator_Email );
			$viewForm->addElement( 'contact/body', $body, t('Body'), new Validator_Length(0, 50000), ($id != null) );
			// Assign some more data to use
			$viewForm->assign( array(
									'ID'		=> $id,
									'OP'		=> $op,
									'FIELDS'	=> $id === null ? null : $this->_model()->getFormFields( $id ),
									));
			$viewForm->assignHtml( array(
										'ACL_FORM' => $this->_acl->buildForm( array(t('View contact form') => $resource) ),
										));
			return $viewForm;
		}

		/**
		 * Adds a new field to an existing contact form
		 *
		 * @return string
		 */
		public function addFieldSection() {
			if ( !$this->_acl->check( 'contact_add' ) ) {
				throw new Module_NoPermission;
			}
			$this->setTitle( t('Add contact form field') );
			$this->setOutputType( self::_OT_CONFIG );
			// Get details of the form this field will attach to
			try {
				$details = $this->_model()->getForm( $this->_router->getArgument('id') );
				// Check user has permission
				$resource = 'contact-form-'.$details['id'];
				if ( !$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource ) ) {
					throw new Module_NoPermission;
				}
				// Build form and check input
				$form = $this->buildFieldForm( $details['id'] );
				if ( $form->hasInput() && $form->isValid() ) {
					$fd = $form->getValues( 'contact' );
					$this->_model()->addField( $details['id'], $fd['name'], $fd['required'], $fd['type'], $fd['options'] );
					$this->_event->success( t('Added contact form field') );
					return zula_redirect( $this->_router->makeUrl( 'contact', 'config', 'edit', null, array('id' => $details['id']) ) );
				} else {
					return $form->getOutput();
				}
			} catch ( Router_ArgNoExist $e ) {
				$this->_event->error( t('No contact form selected') );
			} catch ( Contact_NoExist $e ) {
				$this->_event->error( t('Contact form does not exist') );
			}
			return zula_redirect( $this->_router->makeUrl( 'contact', 'config' ) );
		}

		/**
		 * Edit a contact form field
		 *
		 * @return string
		 */
		public function editFieldSection() {
			if ( !$this->_acl->check( 'contact_edit' ) ) {
				throw new Module_NoPermission;
			}
			$this->setTitle( t('Edit contact form field') );
			$this->setOutputType( self::_OT_CONFIG );
			// Get details of the form field
			try {
				$field = $this->_model()->getField( $this->_router->getArgument('id') );
				// Check user has permission to parent
				$resource = 'contact-form-'.$field['form_id'];
				if ( !$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource ) ) {
					throw new Module_NoPermission;
				}
				// Prepare form and check input
				$form = $this->buildFieldForm( $field['form_id'], $field['name'], $field['required'],
											   $field['type'], $field['options'], $field['id'] );
				if ( $form->hasInput() && $form->isValid() ) {
					$fd = $form->getValues( 'contact' );
					$this->_model()->editfield( $field['id'], $fd['name'], $fd['required'], $fd['type'], $fd['options'] );
					$this->_event->success( t('Edited contact form field' ) );
					return zula_redirect( $this->_router->makeUrl( 'contact', 'config', 'edit', null, array('id' => $field['form_id']) ) );
				} else {
					return $form->getOutput();
				}
			} catch ( Router_ArgNoExist $e ) {
				$this->_event->error( t('No contact form field selected') );
			} catch ( Contact_FieldNoExist $e ) {
				$this->_event->error( t('Contact form field does not exist') );
			}
			return zula_redirect( $this->_router->makeUrl( 'contact', 'config' ) );
		}

		/**
		 * Builds the view for adding/editing a field
		 *
		 * @param int $fid
		 * @param string $name
		 * @param bool $required
		 * @param string $type
		 * @param string $options
		 * @param int $id
		 * @return string
		 */
		protected function buildFieldForm( $fid=null, $name=null, $required=false, $type=null, $options=null, $id=null ) {
			if ( $id === null ) {
				$op = 'add';
				$args = array('id' => $fid);
			} else {
				$op = 'edit';
				$args = array('id' => $id);
			}
			$form = new View_Form( 'config/field_form.html', 'contact', is_null($id) );
			$form->action( $this->_router->makeUrl( 'contact', 'config', $op.'field', null, $args ) );
			$form->addElement( 'contact/name', $name, t('Name'), new Validator_Length(1, 255) );
			$form->addElement( 'contact/required', $required, t('Required'), new Validator_Bool );
			$form->addElement( 'contact/type', $type, t('Type'), new Validator_Alphanumeric );
			$form->addElement( 'contact/options', $options, t('Options'), new Validator_Length(0, 255) );
			// Assign some additional tags
			$form->assign( array(
								'OP'		=> $op,
								'FORM_ID'	=> $fid,
								'ID'		=> $id,
								'TYPES'		=> array(
													'textbox'  	=> t('Textbox'),
													'textarea' 	=> t('Textarea'),
													'radio'    	=> t('Radio options'),
													'checkbox'	=> t('Checkbox'),
													'select'   	=> t('Drop down Menu'),
													'password'	=> t('Password textbox'),
													),
								));
			return $form;
		}

		/**
		 * Creates a bridge between the Delete Selected and Update Order
		 * functionaility, as there can only be one form with one action
		 *
		 * @return mixed
		 */
		public function bridgeSection() {
			$this->setOutputType( self::_OT_CONFIG );
			if ( !$this->_input->checkToken() ) {
				$this->_event->error( Input::csrfMsg() );
			} else if ( $this->_input->has( 'post', 'contact_del_selected' ) ) {
				// Remove all selected form fields
				if ( !$this->_acl->check( 'contact_delete' ) ) {
					throw new Module_NoPermission;
				}
				try {
					$delCount = 0;
					foreach( $this->_input->post( 'contact_field_ids' ) as $fieldId ) {
						try {
							// Check permission to parent form
							$field = $this->_model()->getField( $fieldId );
							$resource = 'contact-form-'.$field['form_id'];
							if ( $this->_acl->resourceExists( $resource ) && $this->_acl->check( $resource ) ) {
								$this->_model()->deleteField( $field['id'] );
								++$delCount;
							}
						} catch ( Contact_FieldNoExist $e ) {
						}
					}
					if ( $delCount ) {
						$this->_event->success( t('Deleted selected form fields') );
					}
				} catch ( Input_KeyNoExist $e ) {
					$this->_event->error( t('No fields selected') );
				}
			} else if ( $this->_input->has( 'post', 'contact_update_order' ) ) {
				// Update the order of the contact form fields
				if ( !$this->_acl->check( 'contact_edit' ) ) {
					throw new Module_NoPermission;
				}
				$sqlQuery = 'UPDATE {PREFIX}mod_contact_fields SET `order` = CASE';
				$sqlMiddle = array();
				$params = array( '' ); # Force key 0 since that wont be used with PDO
				try {
					foreach( $this->_input->post( 'contact_order' ) as $fieldId=>$order ) {
						/**
						* Check user actually has permission to the contact form
						* and that the field exists
						*/
						try {
							$field = $this->_model()->getField( $fieldId );
							$resource = 'contact-form-'.$field['form_id'];
							if ( $this->_acl->resourceExists( $resource ) && $this->_acl->check( $resource ) ) {
								// Set the paramaters that will be bound to the query
								$params[] = $field['id'];
								$params[] = $order;
								$sqlMiddle[] = ' WHEN id = ? THEN ? ';
							}
						} catch ( Contact_FieldNoExist $e ) {
						}
					}
					if ( !empty( $sqlMiddle ) ) {
						$query = $sqlQuery.implode( '', $sqlMiddle ).'ELSE `order` END';
						$pdoSt = $this->_sql->prepare( $query );
						foreach( $params as $ident=>$val ) {
							if ( $ident !== 0 ) {
								$pdoSt->bindValue( $ident, (int) $val, PDO::PARAM_INT );
							}
						}
						$pdoSt->execute();
						$this->_event->success( t('Updated field orders') );
					}
				} catch ( Input_KeyNoExist $e ) {
					$this->_event->error( t('No fields to update order for') );
				}
			}
			try {
				$formId = $this->_router->getArgument('fid');
				$url = $this->_router->makeUrl( 'contact', 'config', 'edit', null, array('id' => $formId) );
				$this->_cache->delete( 'contact_fields_'.$formId );
			} catch ( Router_ArgNoExist $e ) {
				$url = $this->_router->makeUrl( 'contact', 'config' );
			}
			return zula_redirect( $url );
		}

	}

?>
