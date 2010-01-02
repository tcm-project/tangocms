<?php

/**
 * Zula Framework Module (users)
 * --- Configure users, add new users etc
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Users
 */

	class Users_controller_config extends Zula_ControllerBase {

		/**
		 * Amount of users to display per page
		 */
		const _PER_PAGE = 12;

		/**
		 * Constructor
		 * Sets the common page links
		 *
		 * @return object
		 */
		public function __construct( $moduleDetails, $config, $sector ) {
			parent::__construct( $moduleDetails, $config, $sector );
			$this->setPageLinks( array(
										t('Latest Users')		=> $this->_router->makeUrl( 'users', 'config' ),
										t('Add User')			=> $this->_router->makeUrl( 'users', 'config', 'add' ),
										t('Manage Validations')	=> $this->_router->makeUrl( 'users', 'config', 'validation' ),
										));
		}

		/**
		 * Displays the last X registered users.
		 *
		 * @return string
		 */
		public function indexSection() {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('User Management') );
			$this->setOutputType( self::_OT_CONFIG );
			// Check user has correct permission
			if ( !$this->_acl->checkMulti( array('users_add', 'users_edit', 'users_delete') ) ) {
				throw new Module_NoPermission;
			}
			try {
				$curPage = abs( $this->_input->get( 'page' )-1 );
			} catch ( Input_KeyNoExist $e ) {
				$curPage = 0;
			}
			/**
			 * Configure Pagination, build and output the main view file
			 */
			$pagination = new Pagination( $this->_ugmanager->userCount()-1, self::_PER_PAGE );
			$view = $this->loadView( 'config/main.html' );
			$view->assign( array(
								'USERS'	=> $this->_ugmanager->getAllUsers( null, self::_PER_PAGE, ($curPage*self::_PER_PAGE) ),
								));
			$view->assignHtml( array(
									'CSRF'			=> $this->_input->createToken(true),
									'PAGINATION' 	=> $pagination->build(),
									));
			// Autocomplete/suggest feature
			$this->_theme->addJsFile( 'jQuery/plugins/autocomplete.js' );
			$this->_theme->addCssFile( 'jquery.autocomplete.css' );
			$this->addAsset( 'js/autocomplete.js' );
			return $view->getOutput();
		}

		/**
		 * Autocomplete/autosuggest JSON response
		 *
		 * @return false
		 */
		public function autocompleteSection() {
			if ( !_AJAX_REQUEST ) {
				throw new Module_AjaxOnly;
			}
			header( 'Content-Type: text/javascript; charset=utf-8' );
			$searchTitle = '%'.str_replace( '%', '\%', $this->_input->get('query') ).'%';
			$pdoSt = $this->_sql->prepare( 'SELECT id, username FROM {SQL_PREFIX}users WHERE username LIKE ?' );
			$pdoSt->execute( array($searchTitle) );
			// Setup the object to return
			$jsonObj = new StdClass;
			$jsonObj->query = $this->_input->get( 'query' );
			foreach( $pdoSt->fetchAll( PDO::FETCH_ASSOC ) as $row ) {
				$jsonObj->suggestions[] = $row['username'];
				$jsonObj->data[] = $this->_router->makeFullUrl( 'users', 'config', 'edit', 'admin', array('id' => $row['id']) );
			}
			echo json_encode( $jsonObj );
			return false;
		}

		/**
		 * Manages a users validation, either to accept or decline. If declined
		 * the user will be removed so another user can be added with that name.
		 *
		 * @return string
		 */
		public function validationSection() {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('Manage Validations') );
			$this->setOutputType( self::_OT_CONFIG );
			if ( !$this->_acl->check( 'users_manage_validations' ) ) {
				throw new Module_NoPermission;
			}
			// Build form validation
			$form = new View_form( 'config/validation.html', 'users' );
			$form->addElement( 'users/action', null, t('Action'), new Validator_InArray( array('accept', 'decline') ) );
			$form->addElement( 'users/uids', null, t('Users'), new Validator_Is('array') );
			if ( $form->hasInput() && $form->isValid() ) {
				// Activate or Decline/Remove all selected users
				foreach( $form->getValues( 'users/uids' ) as $user ) {
					try {
						$user = $this->_ugmanager->getUser( $user );
						if ( $user['activate_code'] ) {
							if ( $form->getValues( 'users/action' ) == 'accept' ) {
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
					} catch ( UGManager_UserNoExist $e ) {
						$this->_event->error( t('User does not exist') );
					} catch ( Email_Exception $e ) {
						$this->_event->error( t('An error occurred when sending the validation email') );
						$this->_log->message( 'Unable to send validation email: '.$e->getMessage(), Log::L_WARNING );
					} catch ( Exception $e ) {
						$this->_event->error( $e->getMessage() );
					}
				}
				$this->_event->success( $eventMsg );
				return zula_redirect( $this->_router->makeUrl( 'users', 'config', 'validation' ) );
			}
			$form->assign( array('VALIDATIONS' => $this->_ugmanager->awaitingValidation()) );
			return $form->getOutput();
		}

		/**
		 * Handles and displays for the for adding a new user to a specified group.
		 *
		 * @return string|bool
		 */
		public function addSection() {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('Add User') );
			$this->setOutputType( self::_OT_CONFIG );
			if ( !$this->_acl->check( 'users_add' ) ) {
				throw new Module_NoPermission;
			}
			// Form validation
			$form = $this->buildUserForm();
			if ( $form->hasInput() && $form->isValid() ) {
				$details = $form->getValues( 'users' );
				try {
					$uid = $this->_ugmanager->addUser( $details );
					$this->_event->success( sprintf( t('Added user "%1$s"'), $details['username'] ) );
					return zula_redirect( $this->_router->makeUrl( 'users', 'config' ) );
				} catch ( UGManager_GroupNoExist $e ) {
					$this->_event->error( t('Selected group does not exist') );
				} catch ( UGManager_UserExists $e ) {
					$this->_event->error( t('Username already exists') );
				}
			}
			return $form->getOutput();
		}

		/**
		 * Handles and displays form for editing a user.
		 *
		 * @return string|bool
		 */
		public function editSection() {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('Edit User') );
			$this->setOutputType( self::_OT_CONFIG );
			if ( !$this->_acl->check( 'users_edit' ) ) {
				throw new Module_NoPermission;
			}
			// Get the UID and details to edit
			try {
				$user = $this->_ugmanager->getUser( $this->_router->getArgument('id') );
				if ( $user['id'] == UGManager::_GUEST_ID ) {
					$this->_event->error( t('You can not edit the guest user!') );
				} else {
					$this->setTitle( sprintf( t('Edit User "%1$s"'), $user['username'] ), false );
					// Get form and check validation
					$form = $this->buildUserForm( $user );
					if ( $form->hasInput() && $form->isValid() ) {
						$details = $form->getValues( 'users' );
						// Attempt to update the users details
						try {
							$this->_ugmanager->editUser( $user['id'], $details );
							$this->_event->success( sprintf( t('Edited user "%s"'), $details['username'] ) );
							return zula_redirect( $this->_router->makeUrl( 'users', 'config' ) );
						} catch ( UGmanager_GroupNoExist $e ) {
							$this->_event->error( t('Selected group does not exist') );
						} catch ( UGmanager_UserExists $e ) {
							$this->_event->error( t('Username already exists') );
						}
					}
					return $form->getOutput();
				}
			} catch ( Router_ArgNoExist $e ) {
				$this->_event->error( t('No user selected') );
			} catch ( UGmanager_UserNoExist $e ) {
				$this->_event->error( t('User does not exist') );
			}
			return zula_redirect( $this->_router->makeUrl( 'users', 'config' ) );
		}

		/**
		 * Builds the view form to add/edit a user
		 *
		 * @param array $details
		 * @return object
		 */
		protected function buildUserForm( array $details=array() ) {
			$this->_locale->textDomain( $this->textDomain() );
			$op = empty($details['id']) ? 'add' : 'edit';
			$details = zula_merge_recursive( array(
												'id'			=> null,
												'username'		=> null,
												'status'		=> 'active',
												'group'			=> null,
												'first_name'	=> null,
												'last_name'		=> null,
												'email'			=> null,
												'hide_email'	=> true,
												), $details);
			// Build form and validation
			$form = new View_Form( 'config/user_form.html', 'users', empty($details['id']) );
			$form->addElement( 'users/username', $details['username'], t('Username'),
								array(new Validator_Alphanumeric('_()!:@.^-'), new Validator_Length(2, 32), array($this, 'validateUsername'))
							 );
			$form->addElement( 'users/status', $details['status'], t('Status'), new Validator_InArray(array('active', 'locked')) );
			$form->addElement( 'users/group', $details['group'], t('Group'), new Validator_Int, false );
			$form->addElement( 'users/first_name', $details['first_name'], t('First Name'), new Validator_Length(0, 255) );
			$form->addElement( 'users/last_name', $details['last_name'], t('Last Name'), new Validator_Length(0, 255) );
			$form->addElement( 'users/password', null, t('Password'),
								array(new Validator_Length(4, 32),
									  new Validator_Confirm( 'users_password_confirm', Validator_Confirm::_POST )),
								empty($details['id'])
							 );
			$form->addElement( 'users/hide_email', $details['hide_email'], t('Hide Email'), new Validator_Bool );
			// Email validation, we still want to display email when editing remember
			$emailValidation = array(new Validator_Email);
			if ( $op == 'add' || $this->_input->has( 'post', 'users_email_confirm' ) && $this->_input->post( 'users_email_confirm' ) ) {
				$emailValidation[] =  new Validator_Confirm('users_email_confirm', Validator_Confirm::_POST);
			}
			$form->addElement( 'users/email', $details['email'], t('Email'), $emailValidation );
			$form->assign( array(
								'OP' 	=> $op,
								'ID'	=> $details['id'],
								));
			return $form;
		}

		/**
		 * Checks if the username alredy exists, when adding/editing a user
		 *
		 * @param mixed $value
		 * @return bool|string
		 */
		public function validateUsername( $value ) {
			try {
				$details = $this->_ugmanager->getUser( $value, false );
				if ( strtolower($details['username']) == strtolower($value) ) {
					return true;
				} else {
					return t('Username already exists');
				}
			} catch ( Ugmanager_UserNoExist $e ) {
				return true;
			}
		}

		/**
		 * Attempts to delete all selected users
		 *
		 * @return string
		 */
		public function deleteSection() {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setOutputType( self::_OT_CONFIG );
			if ( !$this->_acl->check( 'users_delete' ) ) {
				throw new Module_NoPermission;
			} else if ( !$this->_input->checkToken() ) {
				$this->_event->error( Input::csrfMsg() );
			} else {
				try {
					$delCount = 0;
					foreach( $this->_input->post( 'user_ids' ) as $uid ) {
						try {
							$this->_ugmanager->deleteUser( $uid );
							++$delCount;
						} catch ( UGManager_InvalidUser $e ) {
							$this->_event->error( t('You can not delete the root or guest user')  );
						} catch ( UGManager_UserNoExist $e ) {
						}
					}
					if ( $delCount > 0 ) {
						$this->_event->success( t('Deleted Selected Users') );
					}
				} catch ( Input_KeyNoExist $e ) {
					$this->_event->error( t('No users selected') );
				}
			}
			return zula_redirect( $this->_router->makeUrl( 'users', 'config' ) );
		}

	}

?>
