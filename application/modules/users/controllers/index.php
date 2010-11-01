<?php

/**
 * Zula Framework Module
 * Displays a table of users with pagination
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
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
			$this->setTitle( t('User list') );
			// Get correct page/offset
			if ( $this->inSector('SC') && $this->_input->has( 'get', 'page' ) ) {
				$curPage = abs( $this->_input->get( 'page' )-1 );
			} else {
				$curPage = 0;
			}
			// Gather all of the users and attach the group name onto the array
			$pdoSt = $this->_sql->prepare( 'SELECT
												u.id, u.group, u.username, u.email, u.hide_email, u.joined,
												g.name group_name
											FROM {PREFIX}users u
											LEFT JOIN {PREFIX}groups g ON g.id = u.group
											WHERE u.status = "active" AND u.id != '.Ugmanager::_GUEST_ID.'
											LIMIT :limit OFFSET :offset' );
			$pdoSt->bindValue( ':limit', self::_PER_PAGE, PDO::PARAM_INT );
			$pdoSt->bindValue( ':offset', ($curPage * self::_PER_PAGE), PDO::PARAM_INT );
			$pdoSt->execute();
			$users = array();
			foreach( $pdoSt->fetchAll( PDO::FETCH_ASSOC ) as $user ) {
				if ( (!empty( $user['hide_email'] ) && $user['id'] != $this->_session->getUserId()) && !$this->_acl->check('users_edit') ) {
					$user['email'] = t('Hidden');
				}
				$users[] = $user;
			}
			// How many users would have been returned without limit/offset?
			$query = $this->_sql->query( 'SELECT COUNT(id) FROM {PREFIX}users
											WHERE status = "active" AND id != '.Ugmanager::_GUEST_ID );
			$totalUsers = $query->fetchColumn();
			$query->closeCursor();
			/**
			 * Display the table with pagination
			 */
			$pagination = new Pagination( $totalUsers, self::_PER_PAGE );
			$view = $this->loadView( 'index/main.html' );
			$view->assign( array('users' => $users) );
			$view->assignHtml( array('pagination' => $pagination->build()) );
			return $view->getOutput();
		}

	}

?>
