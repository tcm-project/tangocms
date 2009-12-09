<?php

/**
 * Zula Framework View Helper
 * User/Group form helper
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_View
 */

	class View_Helpers_User extends Zula_LibraryBase {

		/**
		 * Creates an HTML anchor to a users profile
		 *
		 * @param int $userId
		 * @param string $name
		 * @param string $url
		 * @return string
		 */
		public function link( $userId, $name=null, $url=null ) {
			try {
				$user = $this->_ugmanager->getUser( $userId );
				if ( trim( $url ) ) {
					$format = '<a href="%1$s" title="%1$s" rel="nofollow">%3$s</a>';
					$url = zula_htmlspecialchars( zula_url_add_scheme($url) );
				} else if ( $user['id'] == UGManager::_GUEST_ID ) {
					$format = '%3$s';
					$url = '';
				} else {
					$format = '<a href="%1$s" title="%2$s">%3$s</a>';
					$url = $this->_router->makeUrl( 'users', 'profile', $user['id'] );
				}
				return sprintf( $format,
								$url,
								t('View Users Profile', Locale::_DTD),
								zula_htmlspecialchars( (trim($name) ? $name : $user['username']) )
							   );
			} catch ( UGManager_UserNoExist $e ) {
				return t('Unknown User', Locale::_DTD);
			}
		}

		/**
		 * Converts a UID to a Username, with no link
		 *
		 * @param int $userId
		 * @return string
		 */
		public function username( $userId ) {
			try {
				$user = $this->_ugmanager->getUser( $userId );
				return zula_htmlspecialchars( $user['username'] );
			} catch ( UGManager_UserNoExist $e ) {
				return t('Unknown User', Locale::_DTD);
			}
		}

	}

?>
