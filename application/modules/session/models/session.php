<?php

/**
 * Zula Framework Module
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
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
			$pdoSt = $this->_sql->prepare( 'SELECT u.id, u.username, m.value AS activate_code
											FROM {PREFIX}users AS u LEFT JOIN {PREFIX}users_meta AS m
											ON u.id = m.uid AND m.name = "activate_code"
											WHERE u.'.$field.' = ? AND u.password = ?' );
			$pdoSt->execute( array($identifier, zula_hash($password)) );
			$user = $pdoSt->fetch( PDO::FETCH_ASSOC );
			$pdoSt->closeCursor();
			/**
			 * Gather Remote address/ip and either update/insert the failed attempt,
			 * or remove the login attempts due to successful login.
			 */
			$remoteAddr = zula_ip2long( zula_get_client_ip() );
			if ( empty( $user ) ) {
				if ( $this->_sql->getAttribute( PDO::ATTR_DRIVER_NAME ) == 'sqlsrv' ) {
					$pdoSt = $this->_sql->prepare( 'MERGE INTO {PREFIX}mod_session AS dest
									USING (VALUES(?, 1, SYSUTCDATETIME())) AS src(ip, attempts, blocked)
										ON dest.ip = src.ip
									WHEN MATCHED THEN
										UPDATE SET attempts = attempts + 1, blocked = SYSUTCDATETIME()
									WHEN NOT MATCHED THEN
										INSERT (ip, attempts, blocked) VALUES(src.ip, src.attempts, src.blocked);' );
				} else {
					$pdoSt = $this->_sql->prepare( 'INSERT INTO {PREFIX}mod_session (ip, attempts, blocked)
										VALUES (?, 1, UTC_TIMESTAMP())
										ON DUPLICATE KEY UPDATE attempts = attempts+1, blocked = UTC_TIMESTAMP()' );
				}
				$pdoSt->execute( array($remoteAddr) );
				throw new Session_InvalidCredentials;
			} else {
				$pdoSt = $this->_sql->prepare( 'DELETE FROM {PREFIX}mod_session WHERE ip = ?' );
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
			$remoteAddr = (int) zula_ip2long( zula_get_client_ip() );
			$query = $this->_sql->query( 'SELECT attempts, blocked FROM {PREFIX}mod_session WHERE ip = '.$remoteAddr );
			$results = $query->fetch( PDO::FETCH_ASSOC );
			$query->closeCursor();
			if ( $results )  {
				$blockedUntil = $this->_date->getDateTime( $results['blocked'] )
											->modify( '+10 minutes' );
				if ( $blockedUntil < new DateTime ) {
					// Remove the entry as it has now expired
					$this->_sql->exec( 'DELETE FROM {PREFIX}mod_session WHERE ip = '.$remoteAddr );
					$results['attempts'] = 0;
				}
				return $results['attempts'];
			}
			return 0;
		}

		/**
		 * Gets all standard details for users that are awaiting validation
		 * for either all groups, or a specified group.
		 *
		 * @param int $gid
		 * @return array
		 */
		public function getAwaitingValidation( $gid=false ) {
			$query = 'SELECT u.* FROM {PREFIX}users AS u
						JOIN {PREFIX}users_meta AS m ON u.id = m.uid
						WHERE m.name = "activate_code" AND m.value != ""';
			if ( $gid ) {
				$query .= ' AND u.group = '.(int) $gid;
			}
			return $this->_sql->query( $query )->fetchAll( PDO::FETCH_ASSOC );
		}

		/**
		 * Takes a reset code value and attempts to find the UID associated
		 * with it.
		 *
		 * @param string $rc
		 * @return int|false
		 */
		public function resetCodeUid( $rc ) {
			$pdoSt = $this->_sql->prepare( 'SELECT uid FROM {PREFIX}users_meta
											WHERE name = "sessionResetCode" AND value = ? LIMIT 1' );
			$pdoSt->execute( array($rc) );
			$uid = $pdoSt->fetchColumn();
			$pdoSt->closeCursor();
			return $uid ? $uid : false;
		}

	}

?>
