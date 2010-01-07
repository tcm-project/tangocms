<?php

/**
 * Zula Framework UGManager
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
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
		 *
		 * There can *NOT* be more than 1 user in the special 'root'
		 * group, as this user has complete access to anything and
		 * everything. The user in this group should have a very
		 * strong password.
		 *
		 * There can also only be 1 user in the special group 'guest'
		 *
		 * @return object
		 */
		public function __construct() {
			$formats = array(
							'multiple' 	=> 'Multiple "%1$s" users found, only one user can belong in the special "%1$s" group. Additional username: "%2$s"',
							'zero'		=> 'Unable to find the special "%s" user',
							);
			foreach( array('root' => 1, 'guest' => 3) as $group=>$gid ) {
				// Check for multiple root/guest users
				$query = $this->_sql->query( 'SELECT COUNT(id) AS ucount, username FROM {SQL_PREFIX}users WHERE `group` = '.$gid .' GROUP BY id');
				list( $result ) = $query->fetchAll( PDO::FETCH_ASSOC );
				if ( $result['ucount'] > 1 ) {
					trigger_error( sprintf( $formats['multiple'], $group, $result['username'] ), E_USER_ERROR );
				} else if ( $result['ucount'] == 0 ) {
					trigger_error( sprintf( $formats['zero'], $group ), E_USER_ERROR );
				}
			}
		}

		/**
		 * Counts how many users there are for all groups
		 * or a specified group
		 *
		 * @param int $gid
		 * @return int
		 */
		public function userCount( $gid=null ) {
			$gid = !trim($gid) ? '*' : (int) $gid;
			if ( !isset( $this->userCount[ $gid ] ) ) {
				$stmt = 'SELECT COUNT(id) AS ucount FROM {SQL_PREFIX}users';
				if ( is_int( $gid ) ) {
					$stmt .= ' WHERE `group` = '.$gid;
				}
				$query = $this->_sql->query( $stmt );
				list( $count ) = $query->fetchAll( PDO::FETCH_COLUMN );
				$this->userCount[ $gid ] = $count;
			}
			return $this->userCount[ $gid ];
		}

		/**
		 * Counts how many groups there are
		 *
		 * @return int
		 */
		public function groupCount() {
			if ( $this->groupCount == 0 ) {
				$query = $this->_sql->query( 'SELECT COUNT(id) AS gcount FROM {SQL_PREFIX}groups' );
				$result = $query->fetchAll( PDO::FETCH_COLUMN );
				$this->groupCount = $result[0];
			}
			return $this->groupCount;
		}

		/**
		 * Gets details for a single user by username or ID
		 *
		 * @param string|int $user
		 * @param bool $byId
		 * @return array
		 */
		public function getUser( $user, $byId=true ) {
			$user = trim( $user );
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
					throw new UGManager_UserNoExist( 'user '.$user.' does not exist' );
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
			$group = trim( $group );
			if ( $byRoleId === true || !isset( $this->groups[ $group ] ) ) {
				if ( $byRoleId === true ) {
					$col = 'role_id';
				} else {
					$col = ctype_digit($group) ? 'id' : 'name';
				}
				$pdoSt = $this->_sql->prepare(
												'SELECT groups.*, COUNT(users.id) AS user_count
												 FROM
												 	{SQL_PREFIX}groups AS groups
												 	LEFT JOIN {SQL_PREFIX}users AS users ON users.group = groups.id
												 WHERE groups.'.$col.' = ?
												 GROUP BY groups.id'
											   );
				$pdoSt->execute( array( $group ) );
				$details = $pdoSt->fetchAll( PDO::FETCH_ASSOC );
				if ( empty( $details ) ) {
					throw new UGManager_GroupNoExist( 'Unable to get group details. Group/ID "'.$group.'" does not exist' );
				} else {
					$group = $details['0']['id'];
					$this->groups[ $group ] = $details[0];
				}
			}
			return $this->groups[ $group ];
		}

		/**
		 * Gets the details of the groups that inherit from the
		 * provided group ID. This is all done recursively.
		 *
		 * @param int $gid
		 * @param int $level 	Should not really be set by a user
		 * @return array|bool
		 */
		public function groupInherits( $gid, $level=1 ) {
			$groupDetails = $this->getGroup( $gid );
			try {
				$roleDetails = $this->_acl->getRole( $groupDetails['role_id'] );
				if ( empty( $roleDetails['parent_id'] ) ) {
					return false;
				} else {
					$groups = array();
					foreach( $this->getAllGroups() as $group ) {
						if ( $group['role_id'] == $roleDetails['parent_id'] ) {
							$group['depth'] = $level++;
							$inherits = $this->groupInherits( $group['id'], $level );
							if ( is_array( $inherits ) ) {
								$group['inherits'] = $inherits;
							}
							$groups[] = $group;
						}
					}
					return empty($groups) ? false : $groups;
				}
			} catch ( ACL_RoleNoExist $e ) {
				throw new Exception( 'could not get which group "'.$gid.'" inherits. Group has a role_id that does not exist' );
			}
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
			} catch ( UGManager_GroupNoExist $e ) {
				return false;
			}
			return true;
		}

		/**
		 * Gets all users for all groups, or a specified group
		 * with the ability to limit how many are returned
		 *
		 * @param int $group	GroupID to limit by
		 * @param int $limit	Number of users to return
		 * @param int $offset
		 * @param int $order
		 * @return array
		 */
		public function getAllUsers( $group=0, $limit=0, $offset=0, $order=self::_SORT_LATEST ) {
			$cacheable = ($group == 0 && $limit == 0 && $offset == 0 && $order == self::_SORT_LATEST); # Whether users can be got/stored from/in cache
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
		 * Returns every stored group (or a section of them)
		 *
		 * @param int $limit
		 * @param int $offset
		 * @return array
		 */
		public function getAllGroups( $limit=null, $offset=null ) {
			if ( !$groups = $this->_cache->get( 'ugmanager_groups' ) ) {
				// Get groups from SQL database
				$groups = array();
				foreach( $this->_sql->query( 'SELECT * FROM {SQL_PREFIX}groups ORDER BY name', PDO::FETCH_ASSOC ) as $group ) {
					$groups[ $group['id'] ] = $group;
				}
				$this->_cache->add( 'ugmanager_groups', $groups );
			}
			foreach( $groups as $key=>$group ) {
				// Add the amount of users to the array
				$groups[ $key ]['user_count'] = $this->userCount( $group['id'] );
			}
			$this->groups = $groups;
			if ( $limit === null && $offset === null ) {
				return $this->groups;
			} else {
				return $limit === null ? array_slice( $groups, $offset ) : array_slice( $groups, $offset, $limit );
			}
		}

		/**
		 * Converts a GroupID to the Group Name
		 *
		 * @param int $groupId
		 * @return string
		 */
		public function gidName( $groupId ) {
			$grpDetails = $this->getGroup( $groupId );
			return $grpDetails['name'];
		}

		/**
		 * Converts a RoleID to the corret Group ID, if it all
		 * exists.
		 *
		 * If the provided Role ID dos not exist, or a group can
		 * not be found, then bool false will be returned.
		 *
		 * @param int $roleId
		 * @return int|bool
		 */
		public function roleGid( $roleId ) {
			$pdoSt = $this->_sql->prepare( 'SELECT id FROM {SQL_PREFIX}groups WHERE role_id = ?' );
			if ( $pdoSt->execute( array((int) $roleId) ) ) {
				$gid = $pdoSt->fetchAll( PDO::FETCH_COLUMN );
				return empty($gid) ? false : (int) $gid[0];
			} else {
				return false;
			}
		}

		/**
		 * Attempts to add a new group if it does not already exists,
		 * and it will also add the needed ACL roles
		 *
		 * The ID of the new group will be returned
		 *
		 * @param string $name
		 * @param int $inherits
		 * @return int|bool
		 */
		public function addGroup( $name, $inherits=null ) {
			if ( !trim( $name ) ) {
				throw new UGManager_InvalidName( 'provided group name is empty' );
			} else if ( $this->groupExists( $name ) ) {
				throw new UGManager_GroupExists( 'unable to add group "'.$name.'" since it already exists' );
			} else {
				// Attempt to get details for the inheritance group
				if ( !empty( $inherits ) ) {
					try {
						$inheritDetails = $this->getGroup( $inherits );
						$inherits = 'group_'.$inheritDetails['name'];
					} catch ( UGManager_GroupNoExist $e ) {
						throw new UGManager_InvalidInheritance( 'unable to add group, group/role to inherit ('.$inherits.') does not exist' );
					}
				}
				try {
					$roleId = $this->_acl->addRole( 'group_'.$name, $inherits );
				} catch ( ACL_InvalidName $e ) {
					throw new UGManager_InvalidName( $e->getMessage() );
				} catch ( Acl_ParentNoExist $e ) {
					throw new UGManager_InvalidInheritance( 'unable to add group, group/role to inherit ('.$inherits.') does not exist' );
				}
				/**
				 * Add the new group in with the correct details
				 */
				$pdoSt = $this->_sql->prepare( 'INSERT INTO {SQL_PREFIX}groups ( name, role_id ) VALUES ( :name, :role_id )' );
				$result = $pdoSt->execute( array(
										':name'		=> $name,
										':role_id'	=> $roleId,
										));
				if ( $result ) {
					$this->_cache->delete( 'ugmanager_groups' );
					return $this->_sql->lastInsertId();
				} else {
					return false;
				}
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
			try {
				$groupDetails = $this->getGroup( $gid );
				$gid = $groupDetails['id'];
			} catch ( UGManager_GroupNoExist $e ) {
				// Change message
				throw new UGManager_GroupNoExist( 'unable to edit group "'.$gid.'" as it does not exist' );
			}
			if ( !trim( $name ) ) {
				throw new UGManager_InvalidName( 'provided group name is empty' );
			} else if ( $name != $groupDetails['name'] && $this->groupExists( $name ) ) {
				throw new UGManager_GroupExists( 'group "'.$name.'" already exists' );
			} else {
				// Attempt to get details for the inheritance group
				if ( !empty( $inherits ) ) {
					try {
						$inheritDetails = $this->getGroup( $inherits );
						$inherits = 'group_'.$inheritDetails['name'];
					} catch ( UGManager_GroupNoExist $e ) {
						throw new UGManager_InvalidInheritance( 'unable to edit group, group/role to inherit ('.$inherits.') does not exist' );
					}
				}
				try {
					$roleId = $this->_acl->editRole( 'group_'.$groupDetails['name'], $inherits );
				} catch ( ACL_InvalidName $e ) {
					throw new UGManager_InvalidName( $e->getMessage() );
				} catch ( Acl_ParentNoExist $e ) {
					throw new UGManager_InvalidInheritance( 'unable to edit group, group/role to inherit ('.$inherits.') does not exist' );
				}
				// Update group details
				$pdoSt = $this->_sql->prepare( 'UPDATE {SQL_PREFIX}groups SET name = :name WHERE id = :gid' );
				$result = $pdoSt->execute( array(
												':name'			=> $name,
												':gid'			=> $gid,
												));
				if ( $result ) {
					unset( $this->groups[ $gid ], $this->groups[ $groupDetails['name'] ] );
					$this->_cache->delete( 'ugmanager_groups' );
					$pdoSt = null;
					$pdoSt = $this->_sql->prepare( 'UPDATE {SQL_PREFIX}acl_roles SET name = :role_name WHERE id = :role_id' );
					if ( $pdoSt->execute( array(':role_name' => 'group_'.strtolower($name), ':role_id' => $groupDetails['role_id']) ) ) {
						$this->_cache->delete( 'acl_roles' );
						return true;
					} else {
						return false;
					}
				} else {
					return false;
				}
			}
		}

		/**
		 * Deletes a group and all associated ACL Rules with it, as long
		 * with all of the users under it.
		 *
		 * Any groups that used to inherit permissions from it, will no
		 * longer inherit permissions from anything.
		 *
		 * @param int $gid
		 * @return bool
		 */
		public function deleteGroup( $gid ) {
			try {
				$groupDetails = $this->getGroup( $gid );
				$gid = (int) $groupDetails['id'];
				if ( $gid == self::_ROOT_GID || $gid == self::_GUEST_GID ) {
					throw new UGManager_InvalidGroup( 'you can not delete the root or guest group!' );
				}
			} catch ( UGManager_GroupNoExist $e ) {
				throw new UGManager_GroupNoExist( 'unable to delete group "'.$gid.'" as it does not exist' );
			}
			$pdoSt = $this->_sql->prepare( 'DELETE FROM {SQL_PREFIX}groups WHERE id = :gid' );
			if ( $pdoSt->execute( array( ':gid' => $gid ) ) ) {
				$pdoSt = null;
				// Remove all users
				$pdoSt = $this->_sql->prepare( 'DELETE FROM {SQL_PREFIX}users WHERE `group` = :gid' );
				$pdoSt->execute( array( ':gid' => $gid ) );
				// Remove ACL role
				$pdoSt = null;
				$pdoSt = $this->_sql->prepare( 'DELETE FROM {SQL_PREFIX}acl_roles WHERE id = :role_id' );
				$pdoSt->execute( array( ':role_id' => (int) $groupDetails['role_id'] ) );
				// Remove all ACL rules associated with it
				$pdoSt = null;
				$pdoSt = $this->_sql->prepare( 'DELETE FROM {SQL_PREFIX}acl_rules WHERE role_id = :role_id' );
				$pdoSt->execute( array( ':role_id' => (int) $groupDetails['role_id'] ) );
				$this->_cache->delete( 'acl_rules' );
				// Update existing groups that used to inherit this group
				$pdoSt = null;
				$pdoSt = $this->_sql->prepare( 'UPDATE {SQL_PREFIX}acl_roles SET parent_id = 0 WHERE parent_id = :role_id' );
				$pdoSt->execute( array( ':role_id' => (int) $groupDetails['role_id'] ) );
				$this->_cache->delete( 'acl_roles' );
				unset( $this->groups[ $gid ], $this->groups[ $groupDetails['name'] ] );
				foreach( $this->users as $key=>$val ) {
					if ( $val['group'] == $gid ) {
						unset( $this->users[ $key ] );
					}
				}
				$this->_cache->delete( 'ugmanager_users' );
				$this->_cache->delete( 'ugmanager_groups' );
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
			try {
				$groupDetails = $this->getGroup( $gid );
				$gid = (int) $groupDetails['id'];
				if ( $gid == self::_ROOT_GID || $gid == self::_GUEST_GID ) {
					throw new UGManager_InvalidGroup( 'you can not purge the root or guest group!' );
				}
			} catch ( UGManager_GroupNoExist $e ) {
				throw new UGManager_GroupNoExist( 'unable to purge group "'.$gid.'" as it does not exist' );
			}
			$pdoSt = $this->_sql->prepare( 'DELETE FROM {SQL_PREFIX}users WHERE `group` = :gid' );
			if ( $pdoSt->execute( array(':gid' => $gid) ) ) {
				unset( $this->groups[ $gid ], $this->groups[ $groupDetails['name'] ] );
				foreach( $this->users as $key=>$val ) {
					if ( $val['group'] == $gid ) {
						unset( $this->users[ $key ] );
					}
				}
				$this->_cache->delete( 'ugmanager_users' );
				$this->_cache->delete( 'ugmanager_groups' );
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Updates details about a user. A user can not
		 * be moved into the 'root' (GID 1) group, as only
		 * one user is allowed in that group.
		 *
		 * @param int $id
		 * @param array $details
		 * @return bool
		 */
		public function editUser( $id, array $details ) {
			try {
				$userDetails = $this->getUser( $id );
				if ( $userDetails['id'] == UGManager::_GUEST_ID ) {
					throw new UGManager_InvalidUser( 'you can not edit the guest user' );
				} else if ( isset($details['username']) && strtolower($details['username']) != strtolower($userDetails['username'])
							&& $this->userExists( $details['username'], false )
				) {
					throw new UGManager_UserExists( 'you can not rename the user to a user that already exists' );
				}
			} catch ( UGManager_UserNoExist $e ) {
				throw new UGManager_UserNoExist( 'unable to edit user "'.$id.'" as it does not exist' );
			}
			if ( isset( $details['group'] ) ) {
				$userDetails['group'] = (int) $userDetails['group'];
				if ( $userDetails['group'] != self::_ROOT_GID && $details['group'] == self::_ROOT_GID ) {
					throw new UGManager_InvalidGroup( 'user "'.$id.'" can not be moved into the root group, only one user is allowed to be root!' );
				} else if ( $userDetails['group'] == self::_ROOT_GID && $details['group'] != self::_ROOT_GID ) {
					throw new UGManager_InvalidGroup( 'root user can not have its group changed' );
				} else if ( !$this->groupExists( $userDetails['group'] ) ) {
					throw new UGManager_GroupNoExist( 'unable to edit user "'.$id.'" the specified group "'.$userDetails['group'].'" does not exist' );
				}
			}
			if ( empty( $details['password'] ) ) {
				unset( $details['password'] );
			} else {
				$details['password'] = zula_hash( $details['password'] );
			}
			if ( $this->_sql->update( 'users', $details, array('id' => $userDetails['id']) ) ) {
				unset( $this->users[ $userDetails['id'] ], $this->users[ $userDetails['username'] ] );
				$this->_cache->delete( 'ugmanager_users' );
				Hooks::notifyAll( 'ugmanager_user_edit', $userDetails['id'] );
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Adds a new user to a specified group
		 *
		 * @param array $details
		 * @return int|bool	UID
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
				$this->userCount = array();
				$this->_cache->delete( 'ugmanager_users' );
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
			try {
				$user = $this->getUser( $uid );
				if ( $user['id'] == self::_ROOT_ID || $user['id'] == UGManager::_GUEST_ID ) {
					throw new UGManager_InvalidUser( 'unable to delete the root or guest user' );
				}
				$pdoSt = $this->_sql->prepare( 'DELETE FROM {SQL_PREFIX}users WHERE id = ?' );
				$pdoSt->execute( array($user['id']) );
				if ( $pdoSt->rowCount() == 1 ) {
					$this->userCount = array();
					$this->_cache->delete( 'ugmanager_users' );
					Hooks::notifyAll( 'ugmanager_user_delete', $user );
					return true;
				} else {
					return false;
				}
			} catch ( UGManager_UserNoExist $e ) {
				throw new UGManager_UserNoExist( $uid );
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
