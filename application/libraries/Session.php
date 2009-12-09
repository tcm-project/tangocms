<?php

/**
 * Zula Framework Session Handler
 * --- Note: A user is always logged in. Traditional understanding of it, in this, is simply
 * that a user (say 'guest'), switches to a more privileged user account.
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Session
 */

	class Session extends Zula_LibraryBase {

		/**
		 * Authentication key to use when identifying
		 * @var string
		 */
		protected $authKey = null;

		/**
		 * User ID (hashed) to what the auth key is for
		 * @var string
		 */
		protected $authFor = null;

		/**
		 * Contains all info about the curernt user
		 * @var array
		 */
		protected $user = array();

		/**
		 * Holds info of the users group
		 * @var array
		 */
		protected $group = array();

		/**
		 * Timeout to use, for if a user is idle/inactive for a
		 * specified time - it will log them out.
		 * @var int
		 */
		protected $timeout = null;

		/**
		 * Toggles storing of previous raw request path
		 * @var bool
		 */
		protected $storePrevious = true;

		/**
		 * Constructor
		 * Sets certain configuration details, starts the session and then
		 * attempts to get which user is currently in use (either from session
		 * or details within cookies and session table)
		 *
		 * @return object
		 */
		public function __construct() {
			try {
				$this->timeout = abs( $this->_config->get( 'session/timeout' ) );
				if ( $this->timeout == false ) {
					throw new Exception;
				}
			} catch ( Exception $e ) {
				$this->timeout = time(); # Set to a timeout that will never happen.
			}
			if ( session_id() == false ) {
				$conf = array(
							'lifetime'	=> null,
							'path'		=> _BASE_DIR,
							'domain'	=> null,
							'secure'	=> null,
							'httponly'	=> true,
							);
				foreach( $conf as $key=>$val ) {
					if ( $this->_config->has( 'session/'.$key ) ) {
						$conf[ $key ] = $this->_config->get( 'session/'.$key );
					}
				}
				session_set_cookie_params( $conf['lifetime'], $conf['path'], $conf['domain'], $conf['secure'], $conf['httponly'] );
				session_name( 'ZULA_'.md5(_BASE_DIR) );
				session_start();
			}
			if ( _AJAX_REQUEST === true ) {
				// Set default to not store previous URL when in AJAX request
				$this->storePrevious = false;
			}			
			// Get which user (key + for) is being used
			if ( !isset( $_SESSION['auth']['remember'] ) || $_SESSION['auth']['remember'] == true ) {
				if ( $this->_input->has( 'cookie', 'zulaAuthKey' ) ) {
					$this->authKey = $this->_input->cookie( 'zulaAuthKey' );
					if ( $this->_input->has( 'cookie', 'zulaAuthFor' ) ) {
						$this->authFor = $this->_input->cookie( 'zulaAuthFor' );
					}
				}
			} else {
				$this->authKey = $_SESSION['auth']['key'];
				$this->authFor = $_SESSION['auth']['for'];
			}
		}

		/**
		 * Destructor function
		 *
		 * Sets the previous URL to the current URL, so the that next request
		 * can refer to it easily and know whatthe previous request URL was.
		 */
		public function __destruct() {
			if ( Registry::has( 'router' ) ) {
				$this->_zula->resetCwd();
				if ( $this->storePrevious === true && $this->_dispatcher->getStatusCode() != 404 ) {
					$_SESSION['previous_url'] = $this->_router->getRawCurrentUrl();
				}
				$_SESSION['last_activity'] = time();
			}
		}

		/**
		 * Sets if the previous URL should be updated at end of request
		 *
		 * @param bool $store
		 * @return bool
		 */
		public function storePrevious( $store=true ) {
			$this->storePrevious = (bool) $store;
			return true;
		}

		/**
		 * Checks if a user is 'logged in'. A user 'logged in' is basically
		 * anyone not a guest.
		 *
		 * @return bool
		 */
		public function isLoggedIn() {
			return $this->getUserId() != Ugmanager::_GUEST_ID;
		}		

		/**
		 * Takes the authentication details from the cookie and checks if the
		 * provided session key (not ID) is valid and the uid matches up.
		 *
		 * If anything fails, the user will be identified as a guest user.
		 *
		 * @return int
		 */
		public function identify() {
			$this->user = $this->group = array();
			if ( isset( $_SESSION['last_activity'] ) && $_SESSION['last_activity'] + $this->timeout < time() ) {
				// User has timed out
				$this->destroy();
			}			
			if ( $this->authKey && $this->authFor ) {
				$pdoSt = $this->_sql->prepare( 'SELECT uid FROM {SQL_PREFIX}sessions WHERE session_key = ?' );
				$pdoSt->execute( array($this->authKey) );
				$uid = $pdoSt->fetch( PDO::FETCH_COLUMN );
				if ( $uid == false || zula_hash($uid) != $this->authFor ) {
					$uid = Ugmanager::_GUEST_ID;
				}
			} else {
				// Initial identify, do so as guest
				$uid = Ugmanager::_GUEST_ID;
			}
			try {
				$this->user = $this->_ugmanager->getUser( $uid );
				$this->group = $this->_ugmanager->getGroup( $this->user['group'] );
				return $this->user['id'];
			} catch ( Ugmanager_UserNoExist $e ) {
				if ( $uid == Ugmanager::_GUEST_ID ) {
					trigger_error( 'Session::identify() user '.$uid.' does not exist', E_USER_ERROR );
				} else {
					$this->authKey = $this->authFor = null;
					return $this->identify();
				}
			} catch ( Ugmanager_GroupNoExist $e ) {
				trigger_error( 'Session::identify() group '.$this->user['group'].' does not exist', E_USER_ERROR );
			}
		}

		/**
		 * Switches the currently identified user to another account
		 * basically, 'logging in'. All session data will remain.
		 *
		 * @param int $uid
		 * @param bool $remember
		 * @return int|bool
		 */
		public function switchUser( $uid, $remember=true ) {
			$session = $_SESSION;
			$this->destroy(); # Destroy the current session/user first
			session_start();
			$_SESSION = $session;
			// Create the needed unique keys for this session, if any
			$uid = abs( $uid );
			if ( $uid == Ugmanager::_GUEST_ID ) {
				$this->authKey = $this->authFor = null;
				return $this->identify();
			} else {
				$authKey = zula_hash( uniqid(mt_rand().$uid.microtime(true), true) );
				$pdoSt = $this->_sql->prepare( 'INSERT INTO {SQL_PREFIX}sessions (uid, session_key, session_id) VALUES(?, ?, ?)' );
				$pdoSt->execute( array($uid, $authKey, session_id()) );
				if ( $pdoSt->rowCount() ) {
					$this->_sql->query( 'UPDATE {SQL_PREFIX}users SET last_login = UNIX_TIMESTAMP() WHERE id = '.$uid );
					$this->authKey = $authKey;
					$this->authFor = zula_hash( $uid );
					$_SESSION['auth'] = array(
											'remember'	=> (bool) $remember,
											'key'		=> $this->authKey,
											'for'		=> $this->authFor,
											);
					if ( $_SESSION['auth']['remember'] ) { 
						// Set cookie for 28 days
						setcookie( 'zulaAuthKey', $this->authKey, time()+2419200, _BASE_DIR, '', '', true );
						setcookie( 'zulaAuthFor', $this->authFor, time()+2419200, _BASE_DIR, '', '', true );
					}
					return $this->identify();
				} else {
					// Failed to add session entry.
					return false;
				}
			}
		}

		/**
		 * Completly destroys a session and all user/group info
		 * a long with it. Also removes the session cookie.
		 *
		 * @return bool
		 */
		public function destroy() {
			$_SESSION = array();
			foreach( array('zulaAuthKey', 'zulaAuthFor', session_name()) as $cookie ) { 
				setcookie( $cookie, '', time()-42000, _BASE_DIR );
			}
			session_regenerate_id( true );
			session_destroy();
			if ( $this->authKey ) {
				$pdoSt = $this->_sql->prepare( 'DELETE FROM {SQL_PREFIX}sessions WHERE session_key = ?' );
				$pdoSt->execute( array($this->authKey) );
				$pdoSt->closeCursor();
			}
			$this->authKey = $this->authFor = null;
			$this->identify();
			return true;
		}

		/**
		 * Returns details about the current user, if a key is specified
		 * then it will attempt to return just that key
		 *
		 * @param string $key
		 * @return mixed
		 */
		public function getUser( $key=null ) {
			return array_key_exists($key, $this->user) ? $this->user[$key] : $this->user;
		}

		/**
		 * Returns details about the current users group, if a key is specified
		 * then it will attempt to return just that key
		 *
		 * @param string $key
		 * @return mixed
		 */
		public function getGroup( $key=null ) {
			return array_key_exists($key, $this->group) ? $this->group[$key] : $this->group;
		}

		/**
		 * Returns the ID of the current user
		 *
		 * @return int
		 */
		public function getUserId() {
			return isset($this->user['id']) ? $this->user['id'] : Ugmanager::_GUEST_ID;
		}

		/**
		 * Returns the group ID of the current user, or false on failure
		 *
		 * @return int|bool
		 */
		public function getGroupId() {
			return isset($this->group['id']) ? $this->group['id'] : false;
		}

	}

?>
