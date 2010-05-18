<?php

/**
 * Zula Framework Module (users)
 * --- Displays a single user profile
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Users
 */

	class Users_controller_profile extends Zula_ControllerBase {

		/**
		 * Magic __call() function allows for shorter URLS. This will display a
		 * single users profile. If no ID given, then use the current users ID
		 *
		 * @param string $name
		 * @param arary $args
		 * @return mixed
		 */
		public function __call( $name, $arguments ) {
			$uid = substr( $name, 0, -7 );
			try {
				$user = $this->_ugmanager->getUser( ($uid == 'index' ? $this->_session->getUserId() : $uid) );
				$uid = $user['id'];
				if ( $uid == Ugmanager::_GUEST_ID ) {
					throw new Ugmanager_UserNoExist;
				}
				$this->setTitle( sprintf( t('View Profile "%s"'), $user['username'] ) );
				if ( $uid == $this->_session->getUserId() ) {
					// Profile is the current users, display pagelinks to manage own profile
					$this->displayPageLinks();
				}
				$user['group_name'] = $this->_ugmanager->gidName( $user['group'] );
				if ( $uid != $this->_session->getUserId() && $user['hide_email'] && !$this->_acl->check( 'users_edit' ) ) {
					$user['email'] = t('Hidden');
				}
				// Output main view
				$view = $this->loadView( 'profile/profile.html' );
				$view->assign( $user );
				return $view->getOutput();
			} catch ( Ugmanager_UserNoExist $e ) {
				throw new Module_ControllerNoExist;
			}
		}

		/**
		 * Builds the page links to be displayed
		 *
		 * @return bool
		 */
		protected function displayPageLinks() {
			return $this->setPageLinks( array(
											t('View Profile')			=> $this->_router->makeUrl( 'users', 'profile' ),
											t('Edit Profile')			=> $this->_router->makeUrl( 'users', 'profile', 'edit' ),
											t('Edit Account Settings')	=> $this->_router->makeUrl( 'users', 'profile', 'settings' ),
											));
		}

		/**
		 * Allows the user to edit certain parts of his/hers profile
		 *
		 * @return string
		 */
		public function editSection() {
			if ( !$this->_session->isLoggedIn() ) {
				throw new Module_NoPermission;
			}
			$this->_i18n->textDomain( $this->textDomain() );
			$this->setTitle( t('Edit Profile') );			
			// Gather user details and set page links
			$user = $this->_session->getUser();
			$this->displayPageLinks();
			$form = new View_Form( 'profile/edit.html', 'users' );
			$form->addElement( 'users/first_name', $user['first_name'], t('First Name'), new Validator_Length(0, 255) );
			$form->addElement( 'users/last_name', $user['last_name'], t('Last Name'), new Validator_Length(0, 255) );
			if ( $form->hasInput() && $form->isValid() ) {
				try {
					$this->_ugmanager->editUser( $this->_session->getUserId(), $form->getValues('users') );
					$this->_event->success( t('Updated Profile') );
				} catch ( Exception $e ) {
					$this->_event->error( $e->getMessage() );
				}
			}
			return $form->getOutput();
		}

		/**
		 * Allows the current user to edit account details, such as password and email.
		 *
		 * @return string
		 */
		public function settingsSection() {
			if ( !$this->_session->isLoggedIn() ) {
				throw new Module_NoPermission;
			}
			$this->_i18n->textDomain( $this->textDomain() );
			$this->setTitle( t('Edit Account Settings') );
			// Gather user details
			$this->displayPageLinks();
			$user = $this->_session->getUser();
			/**
			 * Prepare form validation
			 */
			$form = new View_Form( 'profile/settings.html', 'users' );
			$form->addElement( 'users/password', null, t('Password'),
								array(new Validator_Length(4,32), new Validator_Confirm('users/password_confirm', Validator_Confirm::_POST)),
								false
							 );
			$form->addElement( 'users/hide_email', $user['hide_email'], t('Hide Email'), new Validator_Bool );
			$form->addElement( 'users/theme', $user['theme'], t('Theme Name'), new Validator_InArray( Theme::getAll() ), false );
			try {
				// Add Email validation if needed
				$emailConf = $this->_input->post( 'users/email_confirm' );
				if ( $emailConf ) {
					$form->addElement( 'users/email', $user['email'], t('Email'), array(new Validator_Email, new Validator_Confirm($emailConf)) );
				} else {
					throw new Exception;
				}
			} catch ( Exception $e ) {
				$form->assign( array('USERS' => array('EMAIL' => $user['email'])) );
			}
			if ( $form->hasInput() && $form->isValid() ) {
				try {
					$fd = $form->getValues( 'users' );
					$this->_ugmanager->editUser( $this->_session->getUserId(), $fd );
					$this->_event->success( t('Updated Profile') );
					return zula_redirect( $this->_router->makeUrl( 'users', 'profile', 'settings' ) );
				} catch ( Exception $e ) {
					$this->_event->error( $e->getMessage() );
				}
			}
			return $form->getOutput();
		}

	}

?>
