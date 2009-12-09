<?php
// $Id: index.php 2768 2009-11-13 18:12:34Z alexc $

/**
 * Zula Framework Module (Users)
 * --- Displays a table of users with pagination
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Users
 */

	class Users_controller_index extends Zula_ControllerBase {

		/**
		 * Amount of users to display per page
		 */
		const _PER_PAGE = 12;

		/**
		 * Displays a selective amount of users per page, with details
		 * about that users and links to their Profile.
		 *
		 * @return string
		 */
		public function indexSection() {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('User List') );
			// Get correct page/offset
			if ( $this->inSector('SC') && $this->_input->has( 'get', 'page' ) ) {
				$curPage = abs( $this->_input->get( 'page' )-1 );
			} else {
				$curPage = 0;
			}
			// Gather all of the users and attach the group name onto the array
			$users = array();
			foreach( $this->_ugmanager->getAllUsers( '', self::_PER_PAGE, ($curPage*self::_PER_PAGE), UGManager::_SORT_ALPHA ) as $user ) {
				if ( $user['id'] != UGManager::_GUEST_ID || $user['activate_code'] ) {
					$user['group_name'] = $this->_ugmanager->gidName( $user['group'] );
					if ( (!empty( $user['hide_email'] ) && $user['id'] != $this->_session->getUserId()) && !$this->_acl->check( 'users_edit' ) ) {
						$user['email'] = t('Hidden');
					}
					$users[] = $user;
				}
			}
			/**
			 * Configure Pagination, build and output the main view file
			 */
			$pagination = new Pagination( $this->_ugmanager->userCount()-1, self::_PER_PAGE );
			$view = $this->loadView( 'index/main.html' );
			$view->assign( array('USERS' => $users) );
			$view->assignHtml( array('PAGINATION' => $pagination->build()) );
			return $view->getOutput();
		}

	}

?>
