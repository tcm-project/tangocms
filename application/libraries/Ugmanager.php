<?php

/**
 * Zula Framework UGManager
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_UGManager
 */

	class Ugmanager extends Zula_LibraryBase {

		/**
		 * Default Guest ID and Username
		 */
		const
			_GUEST_ID 		= 1,
			_ROOT_ID		= 2,
			_GUEST_GID		= 3,
			_ROOT_GID		= 1;

		/**
		 * How to sort the users (when getting all of them)
		 */
		const
			_SORT_ALPHA		= 1,
			_SORT_LATEST	= 2;

		/**
		 * Details of stored users and groups we have details for
		 * @var array
		 */
		protected
					$users 	= array(),
					$groups = array();

		/**
		 * Count of how many users there are in certain groups
		 * @var array
		 */
		protected $userCount = array();

		/**
		 * Count of all groups
		 * @var int
		 */
		protected $groupCount = 0;

		/**
		 * Constructor
		 * There must only ever be 1 user in the root and guest account. If
		 * this differs then halt execution straight away.
		 *
		 * @return object
		 */
		public function __construct() {
			$query = $this->_sql->query( 'SELECT COUNT(id) FROM {SQL_PREFIX}users WHERE `group` IN(1,3)' );
			$uCount = $query->fetchColumn();
			$query->closeCursor();
			if ( $uCount != 2 ) {
				trigger_error( 'Zula Framework expects exactly 1 user in the root and guest group', E_USER_ERROR );
			}
		}

		/**
		 * Returns how many users are in a group, or all groups.
		 *
		 * @param int $gid
		 * @return int
		 */
		public function userCount( $gid=null ) {
			$gid = $gid ? abs($gid) : '*';
			if ( !isset( $this->userCount[ $gid ] ) ) {
				$query = $this->_sql->query(
											'SELECT COUNT(id) FROM {SQL_PREFIX}users
											 WHERE `group` = '.(is_int($gid) ? $gid : '`group`')
											);
				$this->userCount[ $gid ] = $query->fetchColumn();
				$query->closeCursor();
			}
			return $this->userCount[ $gid ];
		}

		/**
		 * Returns how many groups there are
		 *
		 * @return int
		 */
		public function groupCount() {
			if ( $this->groupCount == 0 ) {
				$query = $this->_sql->query( 'SELECT COUNT(id) FROM {SQL_PREFIX}groups' );
				$this->groupCount = $query->fetchColumn();
				$query->closeCursor();
			}
			return $this->groupCount;
		}

		/**
		 * Checks if a user exists by Username or ID
		 *
		 * @param string|int $user
		 * @param bool $byId
		 * @return bool
		 */
		public function userExists( $user, $byId=true ) {
			try {
				$this->getUser( $user, $byId );
				return true;
			} catch ( UGManager_UserNoExist $e ) {
				return false;
			}
		}

		/**
		 * Check if a group exists by name or ID
		 *
		 * @param mixed $group
		 * @return bool
		 */
		public function groupExists( $group ) {
			try {
				$this->getGroup( $group );
				return true;
			} catch ( UGManager_GroupNoExist $e ) {
				return false;
			}
		}

		/**
		 * Gets details for a single user by username or ID
		 *
		 * @param string|int $user
		 * @param bool $byId
		 * @return array
		 */
		public function getUser( $user, $byId=true ) {
			if ( $byId == false || !isset( $this->users[ $user ] ) ) {
				$pdoSt = $this->_sql->prepare( 'SELECT * FROM {SQL_PREFIX}users WHERE '.($byId ? 'id' : 'username').' = ?' );
				$pdoSt->execute( array($user) );
				$userDetails = $pdoSt->fetch( PDO::FETCH_ASSOC );
				$pdoSt->closeCursor();
				if ( $userDetails ) {
					if ( $userDetails['id'] == self::_GUEST_ID ) {
						$userDetails['password'] = '';
					}
					$this->users[ $userDetails['id'] ] = $userDetails;
					$user = $userDetails['id'];
				} else {
					throw new UGManager_UserNoExist( $user );
				}
			}
			return $this->users[ $user ];
		}

		/**
		 * Gets details for a group by name, id or role_id
		 *
		 * @param mixed $group
		 * @param bool $byRoleId
		 * @return array
		 */
		public function getGroup( $group, $byRoleId=false ) {
			if ( $byRoleId === true || !isset( $this->groups[ $group ] ) ) {
				if ( $byRoleId === true ) {
					$col = 'role_id';
				} else {
					$col = ctype_digit( (string) $group ) ? 'id' : 'name';
				}
				$pdoSt = $this->_sql->prepare(
												'SELECT groups.*, COUNT(users.id) AS user_count
												 FROM
												 	{SQL_PREFIX}groups AS groups
												 	LEFT JOIN {SQL_PREFIX}users AS users ON users.group = groups.id
												 WHERE groups.'.$col.' = ?
												 GROUP BY groups.id'
											   );
				$pdoSt->execute( array($group) );
				$groupDetails = $pdoSt->fetch( PDO::FETCH_ASSOC );
				if ( empty( $groupDetails ) ) {
					throw new Ugmanager_GroupNoExist( $group );
				} else {
					return $this->groups[ $groupDetails['id'] ] = $groupDetails;
				}
			}
			return $this->groups[ $group ];
		}

		/**
		 * Gets all users for all groups, or a single specified group. The
		 * result set can be limited.
		 *
		 * @param int $group
		 * @param int $limit
		 * @param int $offset
		 * @param int $order
		 * @return array
		 */
		public function getAllUsers( $group=0, $limit=0, $offset=0, $order=self::_SORT_LATEST ) {
			$cacheable = ($group == 0 && $limit == 0 && $offset == 0 && $order == self::_SORT_LATEST);
			if ( $cacheable && $users = $this->_cache->get( 'ugmanager_users' ) ) {
				$this->users = $users;
				return $users;
			}
			/**
			 * Get all of the users from the SQL database, then hopefully store in cache
			 */
			$statement = 'SELECT * FROM {SQL_PREFIX}users';
			$params = array();
			if ( trim( $group ) ) {
				$statement .= ' WHERE `group` = :group';
				$params[':group'] = $group;
			}
			$orderBy = $order == self::_SORT_LATEST ? 'joined DESC' : 'username';
			$statement .= ' ORDER BY '.$orderBy;
			if ( $limit > 0 ) {
				$statement .= ' LIMIT :limit';
				$params[':limit'] = $limit;
			} else if ( empty( $limit ) && !empty( $offset ) ) {
				$statement .= ' LIMIT 10000000';
			}
			if ( $offset > 0 ) {
				$statement .= ' OFFSET :offset';
				$params[':offset'] = $offset;
			}
			// Prepare and execute query
			$pdoSt = $this->_sql->prepare( $statement );
			foreach( $params as $ident=>$val ) {
				$pdoSt->bindValue( $ident, (int) $val, PDO::PARAM_INT );
			}
			$pdoSt->execute();
			$users = array();
			foreach( $pdoSt->fetchAll( PDO::FETCH_ASSOC ) as $user ) {
				if ( $user['id'] == self::_GUEST_ID ) {
					$user['password'] = '';
				}
				$users[ $user['id'] ] = $user;
				$this->users[ $user['id'] ]= $user;
			}
			if ( $cacheable ) {
				$this->_cache->add( 'ugmanager_users', $users );
			}
			return $users;
		}

		/**
		 * Returns every group
		 *
		 * @return array
		 */
		public function getAllGroups() {
			if ( !$this->groups || !($this->groups = $this->_cache->get('ugmanager_groups')) ) {
				$this->groups = array();
				foreach( $this->_sql->query('SELECT * FROM {SQL_PREFIX}groups ORDER BY name', PDO::FETCH_ASSOC) as $group ) {
					$group['user_count'] = $this->userCount( $group['id'] );
					$this->groups[ $group['id'] ] = $group;
				}
				$this->_cache->add( 'ugmanager_groups', $this->groups );
			}
			return $this->groups;
		}

		/**
		 * Converts a Group ID to the correct group name
		 *
		 * @param int $gid
		 * @return string
		 */
		public function gidName( $gid ) {
			$group = $this->getGroup( $gid );
			return $group['name'];
		}

		/**
		 * Converts an ACL role id to the correct group id. Each group
		 * is attached to a role, which is how the connection is made.
		 *
		 * @param int $roleId
		 * @return int|bool
		 */
		public function roleGid( $roleId ) {
			$query = $this->_sql->query( 'SELECT id FROM {SQL_PREFIX}groups WHERE role_id = '.(int) $roleId );
			$gid = $query->fetchColumn();
			$query->closeCursor();
			return $gid ? abs($gid) : false;
		}

		/**
		 * Adds a new group if it does not already exists. An ACL role is also
		 * created using the name of the group.
		 *
		 * The ID of the new group will be returned
		 *
		 * @param string $name
		 * @param int $inherits
		 * @return int|bool
		 */
		public function addGroup( $name, $inherits=null ) {
			if ( $this->groupExists( $name ) ) {
				throw new UGManager_GroupExists( $name );
			} else {
				// Attempt to get details for the inheritance group
				if ( !empty( $inherits ) ) {
					try {
						$inheritDetails = $this->getGroup( $inherits );
						$inherits = 'group_'.$inheritDetails['name'];
					} catch ( UGManager_GroupNoExist $e ) {
						throw new UGManager_InvalidInheritance( $inherits );
					}
				}
				try {
					$roleId = $this->_acl->addRole( 'group_'.strtolower($name), $inherits );
				} catch ( Acl_ParentNoExist $e ) {
					throw new UGManager_InvalidInheritance( $inherits );
				}
				// Add in the new group
				$pdoSt = $this->_sql->prepare( 'INSERT INTO {SQL_PREFIX}groups (name, role_id) VALUES (?, ?)' );
				$pdoSt->execute( array($name, $roleId) );
				$pdoSt->closeCursor();
				$this->_cache->delete( 'ugmanager_groups' );
				return $this->_sql->lastInsertId();
			}
		}

		/**
		 * Updates details about an already existing group
		 *
		 * @param int $gid
		 * @param string $name
		 * @param int $inherits
		 * @return bool
		 */
		public function editGroup( $gid, $name, $inherits=null ) {
			$group = $this->getGroup( $gid );
			$gid = $group['id'];
			if ( $name != $group['name'] && $this->groupExists( $name ) ) {
				throw new UGManager_GroupExists( 'unable to rename group, new name already exists' );
			} else {
				// Attempt to get details for the inheritance group
				if ( !empty( $inherits ) ) {
					try {
						$inheritDetails = $this->getGroup( $inherits );
						$inherits = 'group_'.$inheritDetails['name'];
					} catch ( UGManager_GroupNoExist $e ) {
						throw new UGManager_InvalidInheritance( $inherits );
					}
				}
				try {
					$roleId = $this->_acl->editRole( 'group_'.$group['name'], $inherits );
				} catch ( Acl_ParentNoExist $e ) {
					throw new UGManager_InvalidInheritance( $inherits );
				}
				// Update group details
				$pdoSt = $this->_sql->prepare( 'UPDATE {SQL_PREFIX}groups SET name = ? WHERE id = ?' );
				$pdoSt->execute( array($name, $gid) );
				// Update the ACL Role name as well, to keep it the same as the group
				$pdoSt->closeCursor();
				$pdoSt = $this->_sql->prepare( 'UPDATE {SQL_PREFIX}acl_roles SET name = ? WHERE id = ?' );
				$pdoSt->execute( array('group_'.strtolower($name), $group['role_id']) );
				// Cleanup and remove cache
				$this->_cache->delete( array('acl_roles', 'ugmanager_groups') );
				unset( $this->groups[ $gid ] );
				return true;
			}
		}

		/**
		 * Deletes a group and all associated ACL rules/roles with it. All users
		 * within the group will also be deleted.
		 *
		 * Any groups that used to inherit permissions from it, will inherit from
		 * nothing.
		 *
		 * @param int $gid
		 * @return bool
		 */
		public function deleteGroup( $gid ) {
			$group = $this->getGroup( $gid );
			$gid = abs( $group['id'] );
			if ( $gid == self::_ROOT_GID || $gid == self::_GUEST_GID ) {
				throw new Ugmanager_InvalidGroup( 'you can not delete the root or guest group' );
			}
			if ( $this->_sql->exec('DELETE FROM {SQL_PREFIX}groups WHERE id = '.$gid) ) {
				$this->_sql->exec( 'DELETE FROM {SQL_PREFIX}users WHERE `group` = '.$gid );
				// Remove all ACL roles and rules associated with it
				$this->_sql->exec( 'DELETE FROM {SQL_PREFIX}acl_roles WHERE id = '.(int) $group['role_id'] );
				$this->_sql->exec( 'DELETE FROM {SQL_PREFIX}acl_rules WHERE role_id = '.(int) $group['role_id'] );
				// Update existing groups that used to inherit this group
				$this->_sql->exec( 'UPDATE {SQL_PREFIX}acl_roles SET parent_id = 0 WHERE parent_id = '.(int) $group['role_id'] );
				// Cleanup and remove cache
				foreach( $this->users as $key=>$val ) {
					if ( $val['group'] == $gid ) {
						unset( $this->users[ $key ] );
					}
				}
				unset( $this->groups[ $gid ], $this->userCount['*'], $this->userCount[ $gid ] );
				$this->_cache->delete( array('ugmanager_users', 'ugmanager_groups', 'acl_roles') );
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Purges a group of all its users
		 *
		 * @param int $gid
		 * @return bool
		 */
		public function purgeGroup( $gid ) {
			$group = $this->getGroup( $gid );
			$gid = abs( $group['id'] );
			if ( $gid == self::_ROOT_GID || $gid == self::_GUEST_GID ) {
				throw new Ugmanager_InvalidGroup( 'you can not purge the root or guest group!' );
			}
			$this->_sql->exec( 'DELETE FROM {SQL_PREFIX}users WHERE `group` = '.$gid );
			// Remove cache and cleanup
			foreach( $this->users as $key=>$val ) {
				if ( $val['group'] == $gid ) {
					unset( $this->users[ $key ] );
				}
			}
			unset( $this->groups[ $gid ], $this->userCount['*'], $this->userCount[ $gid ] );
			$this->_cache->delete( array('ugmanager_users', 'ugmanager_groups') );
			return true;
		}

		/**
		 * Updates details about a user. A user can not be moved into the root group
		 * and the guest use can not be edited.
		 *
		 * @param int $uid
		 * @param array $details
		 * @return bool
		 */
		public function editUser( $uid, array $details ) {
			$user = $this->getUser( $uid );
			if ( $user['id'] == Ugmanager::_GUEST_ID ) {
				throw new Ugmanager_InvalidUser( 'you can not edit the guest user' );
			} else if ( isset($user['username']) && strtolower($details['username']) != strtolower($user['username'])
						&& $this->userExists( $details['username'], false )
			) {
				throw new Ugmanager_UserExists( 'you can not rename the user to a user that already exists' );
			}
			if ( isset( $details['group'] ) ) {
				if ( $user['group'] != self::_ROOT_GID && $details['group'] == self::_ROOT_GID ) {
					throw new Ugmanager_InvalidGroup( 'user can not be moved into the root group' );
				} else if ( $user['group'] == self::_ROOT_GID ) {
					// Root user must always be in the root group, so override any changes
					$details['group'] = self::_ROOT_GID;
				} else if ( !$this->groupExists( $details['group'] ) ) {
					throw new Ugmanager_GroupNoExist( $details['group'] );
				}
			}
			if ( empty( $details['password'] ) ) {
				unset( $details['password'] );
			} else {
				$details['password'] = zula_hash( $details['password'] );
			}
			if ( $this->_sql->update( 'users', $details, array('id' => $user['id']) ) ) {
				unset( $this->users[ $user['id'] ] );
				$this->_cache->delete( 'ugmanager_users' );
				Hooks::notifyAll( 'ugmanager_user_edit', $user['id'] );
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Adds a new user to a specified group
		 *
		 * @param array $details
		 * @return int|bool
		 */
		public function addUser( $details ) {
			if ( !isset( $details['group'] ) || !$this->groupExists( $details['group'] ) ) {
				throw new UGManager_GroupNoExist( 'could not add user to group as it does not exist' );
			} else if ( $details['group'] == self::_ROOT_GID || $details['group'] == self::_GUEST_GID ) {
				throw new UGManager_InvalidGroup( 'users can not be added to the root or guest group' );
			} else if ( $this->userExists( $details['username'], false ) ) {
				throw new UGManager_UserExists( 'could not add user as user already exists' );
			}
			if ( isset( $details['password'] ) ) {
				$details['password'] = zula_hash( $details['password'] );
			}
			$details['joined'] = date('Y-m-d H:i:s');
			if ( $this->_sql->insert( 'users', $details ) ) {
				unset( $this->userCount['*'], $this->userCount[ $details['group'] ] );
				$this->_cache->delete( 'ugmanager_users' );
				// Add the ID so hooks can use it.
				$details['user_id'] = $this->_sql->lastInsertId();
				Hooks::notifyAll( 'ugmanager_user_add', $details );
				return $details['user_id'];
			} else {
				return false;
			}
		}

		/**
		 * Deletes a user
		 *
		 * @param int $uid
		 * @return bool
		 */
		public function deleteUser( $uid ) {
			$user = $this->getUser( $uid );
			if ( $user['id'] == self::_ROOT_ID || $user['id'] == self::_GUEST_ID ) {
				throw new Ugmanager_InvalidUser( 'root or guest user can not be deleted' );
			}
			if ( $this->_sql->exec('DELETE FROM {SQL_PREFIX}users WHERE id = '.$user['id']) ) {
				unset( $this->userCount['*'], $this->userCount[ $user['group'] ] );
				$this->_cache->delete( 'ugmanager_users' );
				Hooks::notifyAll( 'ugmanager_user_delete', $user );
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Gets all of the users that are awaiting validation for
		 * all groups, or a specified group
		 *
		 * @param int $group	GroupID to limit by
		 * @return array
		 */
		public function awaitingValidation( $group=false ) {
			try {
				$users = $this->getAllUsers( $group );
			} catch ( UGManager_GroupNoExist $e ) {
				// Change the message a bit =)
				throw new UGManager_GroupNoExist( 'could not get users awaiting validation for group "'.$group.'" as the group does not exist' );
			}
			$validations = array();
			foreach( $users as $user ) {
				if ( !empty( $user['activate_code'] ) && $user['id'] != self::_GUEST_ID ) {
					$validations[] = $user;
				}
			}
			return $validations;
		}

		/**
		 * Attempts to activate a user by removing the activation code
		 * from the row and then returning the UserID it activated
		 *
		 * @param string $code
		 * @return int
		 */
		public function activateUser( $code ) {
			$pdoSt = $this->_sql->prepare( 'SELECT id, activate_code FROM {SQL_PREFIX}users WHERE activate_code = ? LIMIT 1' );
			$pdoSt->execute( array( $code ) );
			$result = $pdoSt->fetchAll( PDO::FETCH_ASSOC );
			if ( empty( $result ) ) {
				throw new UGManager_InvalidActivationCode( 'no user with the activation code "'.$code.'" could be found' );
			} else {
				// Gather the UserID and update (or remove) the Activate code
				$result = $result[0];
				$userId = &$result['id'];
				$query = $this->_sql->prepare( 'UPDATE {SQL_PREFIX}users SET activate_code = :code WHERE id = :id' );
				$query->execute( array(
										':code'	=> '',
										':id'	=> $userId,
										));
				if ( $query->rowCount() == 1 ) {
					$this->_cache->delete( 'ugmanager_users' );
					return $userId;
				} else {
					throw new UGManager_InvalidActivationCode( 'no user with the activation code "'.$code.'" could be found' );
				}
			}
		}

		/**
		 * Resets a users password, and also removes the reset-code. The
		 * user ID of the effected user will be returned.
		 *
		 * @param string $code
		 * @param string $password
		 * @return int
		 */
		public function resetPassword( $code, $password ) {
			$code = trim( $code );
			$pdoSt = $this->_sql->prepare( 'SELECT id, reset_code FROM {SQL_PREFIX}users WHERE reset_code = ? LIMIT 1' );
			$pdoSt->execute( array( $code ) );
			$results = $pdoSt->fetchAll( PDO::FETCH_ASSOC );
			if ( empty( $results ) ) {
				throw new UGManager_InvalidResetCode( 'no user with reset code "'.$code.'" could be found' );
			} else {
				$userId = $results[0]['id'];
				// Update users reset-code and password
				$query = $this->_sql->prepare( 'UPDATE {SQL_PREFIX}users SET reset_code = :code, password = :password WHERE id = :id' );
				$query->execute( array(
										':code'		=> '',
										':id'		=> $userId,
										'password'	=> zula_hash( $password ),
										));
				if ( $query->rowCount() == 1 ) {
					$this->_cache->delete( 'ugmanager_users' );
					return $userId;
				} else {
					throw new UGManager_InvalidResetCode( 'no user with reset code "'.$code.'" could be found' );
				}
			}
		}

		/**
		 * Checks if an email address has been taken by another user
		 *
		 * @param string $email
		 * @return bool
		 */
		public function emailTaken( $email ) {
			if ( trim( $email ) ) {
				foreach( $this->getAllUsers() as $user ) {
					if ( strtolower( $email ) == strtolower( $user['email'] ) ) {
						return true;
					}
				}
			}
			return false;
		}

		/**
		 * Finds/Searches users by phrase either for Email or Username
		 *
		 * @param string $phrase
		 * @param string $element
		 * @param int $limit
		 * @param int $offset
		 * @return array|bool
		 */
		public function findUsers( $phrase, $element='username', $limit=0, $offset=0 ) {
			if ( empty( $limit ) && empty( $offset ) ) {
				$pdoSt = $this->_sql->prepare( 'SELECT * FROM {SQL_PREFIX}users WHERE '.$element.' = ?' );
				$pdoSt->execute( array( '%'.$phrase.'%' ) );
				return $pdoSt->fetchAll( PDO::FETCH_ASSOC );
			} else {
				// Build the correct prepared statement
				$statement = 'SELECT * FROM {SQL_PREFIX}users WHERE '.$element.' LIKE :phrase';
				$params = array();
				if ( $limit > 0 ) {
					$statement .= ' LIMIT :limit';
					$params[':limit'] = $limit;
				} else if ( empty( $limit ) && !empty( $offset ) ) {
					$statement .= ' LIMIT 1000000';
				}
				if ( $offset > 0 ) {
					$statement .= ' OFFSET :offset';
					$params[':offset'] = $offset;
				}
				// Prepare and execute query
				$pdoSt = $this->_sql->prepare( $statement );
				foreach( $params as $ident=>$val ) {
					$pdoSt->bindValue( $ident, (int) $val, PDO::PARAM_INT );
				}
				$pdoSt->bindValue( ':phrase', '%'.$phrase.'%' );
				$pdoSt->execute();
				return $pdoSt->fetchAll( PDO::FETCH_ASSOC );
			}
		}

	}

?>
