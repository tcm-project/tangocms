<?php

/**
 * Zula Framework Model (Session)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Session
 */

	class Session_model extends Zula_ModelBase {

		/**
		 * Checks if the given credentials are valid and the account has
		 * actually be activiated. ID of the user is returned.
		 *
		 * @param string $identifier
		 * @param string $password		plain text password
		 * @param string $method		Username or Email Address
		 * @return int
		 */
		public function checkCredentials( $identifier, $password, $method='username' ) {
			$field = $method == 'username' ? 'username' : 'email';
			$pdoSt = $this->_sql->prepare( 'SELECT id, username, activate_code FROM {SQL_PREFIX}users WHERE '.$field.' = ? AND password = ?' );
			$pdoSt->execute( array($identifier, zula_hash($password)) );
			$user = $pdoSt->fetch( PDO::FETCH_ASSOC );
			$pdoSt->closeCursor();
			/**
			 * Gather Remote address/ip and either update/insert the failed attempt,
			 * or remove the login attempts due to successful login.
			 */
			$remoteAddr = zula_ip2long( $_SERVER['REMOTE_ADDR'] );
			if ( empty( $user ) ) {
				$pdoSt = $this->_sql->prepare( 'INSERT INTO {SQL_PREFIX}mod_session ( ip, attempts ) VALUES ( ?, 1 )
												ON DUPLICATE KEY UPDATE attempts = attempts+1' );
				$pdoSt->execute( array($remoteAddr) );
				throw new Session_InvalidCredentials;
			} else {
				$pdoSt = $this->_sql->prepare( 'DELETE FROM {SQL_PREFIX}mod_session WHERE ip = ?' );
				$pdoSt->execute( array($remoteAddr) );
			}
			// Update everything needed to set the user has logged in.
			if ( $user['activate_code'] ) {
				throw new Session_UserNotActivated( 'User "'.$user['username'].'" is not yet activated' );
			} else {
				return $user['id'];
			}
		}

		/**
		 * Gets the number of login attempts for the current remote addr
		 *
		 * @return int
		 */
		public function getLoginAttempts() {
			$remoteAddr = (int) zula_ip2long( $_SERVER['REMOTE_ADDR'] );
			$query = $this->_sql->query( 'SELECT attempts, blocked FROM {SQL_PREFIX}mod_session WHERE ip = '.$remoteAddr );
			$results = $query->fetch( PDO::FETCH_ASSOC );
			$query->closeCursor();
			if ( $results )  {
				if ( $this->_date->utcStrtotime( $results['blocked'].' +10 minutes' ) <= time() ) {
					// Remove the entry as it has now expired
					$this->_sql->exec( 'DELETE FROM {SQL_PREFIX}mod_session WHERE ip = '.$remoteAddr );
					$results['attempts'] = 0;
				}
				return $results['attempts'];
			}
			return 0;
		}

	}

?>
