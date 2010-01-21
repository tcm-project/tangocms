<?php

/**
 * Zula Framework Access Control Levels/Lists (Deny all default)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Jamal Fanaian
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Acl
 */

	class Acl extends Zula_LibraryBase {

    	const
			// Regex that resource and role names must match
    		_REGEX_PATTERN = '#[A-Z0-9_\-]#i',

			// Behaviour when checking multiple resources
    		_MULTI_ONE	= 1,
    		_MULTI_ALL	= 2;

		/**
		 * Every ACL resource there is
		 * @var array
		 */
		protected $resources = array();

		/**
		 * Every ACL role
		 * @var array
		 */
		protected $roles = array();

		/**
		 * Every ACL rule
		 * @var array
		 */
		protected $rules = array();

		/**
		 * Holds status if ACL is enabled or not
		 * @param bool
		 */
		protected $enabled = false;

		/**
		 * Constructor
		 *
		 * @return object
		 */
		public function __construct() {
			if ( Registry::has( 'sql' ) ) {
				$this->getAllRules();
				$this->getAllResources();
				$this->getAllRoles();
			} else {
				trigger_error( 'Access Control Levels (ACL) needs SQL connection, unable to retrieve ACL rules', E_USER_ERROR );
			}
		}

		/**
		 * Removes any broken ACL rules (ones that have missing ACL resource)
		 *
		 * @return int
		 */
		public function cleanRules() {
			$this->_cache->delete( 'acl_rules' );
			return $this->_sql->exec( 'DELETE rules
										FROM {SQL_PREFIX}acl_rules AS rules
											LEFT JOIN {SQL_PREFIX}acl_resources AS resources
											ON rules.resource_id = resources.id
										WHERE resources.id IS NULL' );
		}

		/**
		 * Gets every rule (relationship of Resources and Roles)
		 *
		 * @return array
		 */
		public function getAllRules() {
			$this->rules = $this->_cache->get( 'acl_rules' );
			if ( empty( $this->rules ) ) {
				$this->rules = array();
				foreach( $this->_sql->query( 'SELECT * FROM {SQL_PREFIX}acl_rules', PDO::FETCH_ASSOC ) as $row ) {
					$this->rules[ $row['id'] ] = $row;
				}
				$this->_cache->add( 'acl_rules', $this->rules );
			}
			return $this->rules;
		}

		/**
		 * Gets every role (a user or group for example). If a
		 * prefix is provided, then it will get all roles that
		 * begin with the prefix.
		 *
		 * @param string $prefix
		 * @return array
		 */
		public function getAllRoles( $prefix='' ) {
			$this->roles = $this->_cache->get( 'acl_roles' );
			if ( empty( $this->roles ) ) {
				$this->roles = array();
				foreach( $this->_sql->query( 'SELECT * FROM {SQL_PREFIX}acl_roles ORDER BY parent_id ASC', PDO::FETCH_ASSOC ) as $row ) {
					$this->roles[ $row['name'] ] = $row;
				}
				$this->_cache->add( 'acl_roles', $this->roles );
			}
			if ( empty( $prefix ) ) {
				return $this->roles;
			} else {
				$roles = array();
				foreach( $this->roles as $role ) {
					if ( substr( $role['name'], 0, strlen($prefix) ) == $prefix ) {
						$roles[] = $role;
					}
				}
				return $roles;
			}
		}

		/**
		 * Gets every resource (article_view for example). If a
		 * prefix is provided, then it will get all resources that
		 * begin with the prefix
		 *
		 * @param string $prefix
		 * @return array
		 */
		public function getAllResources( $prefix='' ) {
			$this->resources = $this->_cache->get( 'acl_resources' );
			if ( empty( $this->resources ) ) {
				$this->resources = array();
				foreach( $this->_sql->query( 'SELECT * FROM {SQL_PREFIX}acl_resources', PDO::FETCH_ASSOC ) as $row ) {
					$this->resources[ $row['name'] ] = $row;
				}
				$this->_cache->add( 'acl_resources', $this->resources );
			}
			if ( empty( $prefix ) ) {
				return $this->resources;
			} else {
				$resources = array();
				foreach( $this->resources as $resource ) {
					if ( substr( $resource['name'], 0, strlen($prefix) ) == $prefix ) {
						$resources[] = $resource;
					}
				}
				return $resources;
			}
		}

		/**
		 * Checks if an ACL resource exists
		 *
		 * @param string $resource
		 * @return bool
		 */
		public function resourceExists( $resource ) {
			try {
				$this->getResource( $resource );
				return true;
			} catch ( Acl_ResourceNoExist $e ) {
				return false;
			}
		}

		/**
		 * Checks if an ACL role exists
		 *
		 * @param string $role
		 * @return bool
		 */
		public function roleExists( $role ) {
			try {
				$this->getRole( $role );
				return true;
			} catch ( Acl_RoleNoExist $e ) {
				return false;
			}
		}

		/**
		 * Gets the role data based on a role id, naem or if set
		 * it will attempt to get it by the 'parent_id' value
		 *
		 * @param string|int $role
		 * @param bool $byPid
		 * @return array	Array containing all of the role details or
		 *					false if no role was found
		 */
		public function getRole( $role, $byPid=false ) {
			$byPid = (bool) $byPid;
			$roles = array();
			foreach( $this->getAllRoles() as $tmpRole ) {
				if (
					($byPid == false && ($tmpRole['name'] == $role || $tmpRole['id'] == $role))
					||
					($byPid && $tmpRole['parent_id'] == $role)
				) {
					if ( $byPid === true ) {
						$roles[] = $tmpRole;
					} else {
						return $tmpRole;
					}
				}
			}
			if ( $byPid === false ) {
				throw new Acl_RoleNoExist( 'role "'.$role.'" does not exist. By PID: '.zula_bool2str( $byPid ) );
			} else if ( count( $roles ) == 1 ) {
				return $roles[0];
			} else {
				return $roles;
			}
		}

		/**
		 * Gets the resource data based on a resource ID or name
		 *
		 * @param string|int $resource
		 * @return mixed
		 */
		public function getResource( $resource ) {
			foreach( $this->getAllResources() as $tmpResource ) {
				if ( $tmpResource['name'] == $resource || $tmpResource['id'] == $resource ) {
					return $tmpResource;
				}
			}
			throw new Acl_ResourceNoExist( 'resource "'.$resource.'" does not exist' );
		}

		/**
		 * Gets the hierarchy tree of all the parent roles for specified role.
		 *
		 * The tree will start with the specified role at the first level and
		 * each parent as the next.
		 *
		 * @param string|int $role 	The role identifier (id or name)
		 * @param bool $forwards 	Reverse the tree and walk downwards
		 * @param int $iteration 	Not really to be set by a user
		 * @return mixed
		 */
		public function getRoleTree( $role, $reverse=false, $iteration=0 ) {
			try {
				$role = $iteration === 0 ? $this->getRole( $role ) : $this->getRole( $role, $reverse );
				if ( isset( $role['parent_id'] ) ) {
					$role = array( $role );
				}
			} catch ( Acl_RoleNoExist $e ) {
				return false;
			}
			// Create array to hold role tree
			$roles = array();
			foreach( $role as $tmpRole ) {
				$tmpRole['level'] = $iteration;
				$roles[] = $tmpRole;
				if ( empty( $tmpRole['parent_id'] ) && $reverse === false ) {
					return $roles;
				} else if ( $reverse === true ) {
					$branch = $this->getRoleTree( $tmpRole['id'], true, $iteration+1 );
				} else if ( $reverse === false ) {
					$branch = $this->getRoleTree( $tmpRole['parent_id'], false, $iteration+1 );
				}
				if ( is_array( $branch ) ) {
					$roles = array_merge( $roles, $branch );
				}
			}
			return $roles;
		}

		/**
		 * Creates a new role
		 *
		 * If add_rule is called with a different parent on an existing role
		 * the role will be updated with the new parent.
		 *
		 * @param string $name
		 * @param string|int $parent	Role identifier for parent (id or name)
		 * @return int
		 */
		public function addRole( $name, $parent=null ) {
			if ( !preg_match( self::_REGEX_PATTERN, $name ) ) {
				throw new Acl_InvalidName( 'role names must only contain alphanumeric chars, underscore and hyphen (A-Z, a-z, 0-9, _, -)' );
			}
			$name = strtolower( $name );
			$parent = strtolower( $parent );
			// Get parent details if they were specified
			if ( empty( $parent ) ) {
				$parent = 0;
			} else {
				try {
					$parent = $this->getRole( $parent );
					if ( $parent['name'] == 'group_root' ) {
						throw new Acl_InvalidParent( 'ACL role can not inherit from root role' );
					}
					$parent = $parent['id'];
				} catch ( Acl_RoleNoExist $e ) {
					throw new Acl_ParentNoExist( 'role "'.$name.'" could not be added, parent role "'.$parent.'" does not exist' );
				}
			}
			try {
				$role = $this->getRole( $name );
				// The specified rule exists, see if the parent is being updated
				if ( $parent != $role['parent_id'] ) {
					if ( $parent != 0 ) {
						// Check that the new parent is not a child of it's current self
						foreach( $this->getRoleTree( $role['id'], true ) as $child ) {
							if ( $child['id'] == $parent ) {
								throw new Acl_InvalidParent( 'ACL role can not inherit from one of its children' );
							}
						}
					}
					$details = array( 'parent_id' => $parent );
					$this->_sql->update( 'acl_roles', $details, array('id' => $role['id']) );
				}
				$this->_cache->delete( 'acl_roles' );
				return $role['id'];
			} catch ( Acl_RoleNoExist $e ) {
				// Rule does not exist so create new
				$details = array(
								'name'		=> $name,
								'parent_id'	=> $parent,
								);
				$this->_sql->insert( 'acl_roles', $details );
				$this->roles[ $name ] = array_merge( array( 'id' => $this->_sql->lastInsertId() ), $details );
				$this->_cache->delete( 'acl_roles' );
				return $this->_sql->lastInsertId();
			}
		}

		/**
		 * Edits an existing role, or will add it if needed
		 *
		 * @param string $name
		 * @param string|int $parent	Role identified
		 * @see ACL::add_role()
		 * @return int
		 */
		public function editRole( $name, $parent=null ) {
			return $this->addRole( $name, $parent );
		}

		/**
		 * Deletes a Role from the SQL table as well as the
		 * ACL rules associated with it
		 *
		 * @param string|int $role
		 * @return bool
		 */
		public function deleteRole( $role ) {
			try {
				$roleDetails = $this->getRole( $role );
				$this->_sql->delete( 'acl_roles', array( 'id' => $roleDetails['id'] ) );
				$this->_sql->delete( 'acl_rules', array( 'role_id'	=> $roleDetails['id'] ) );
				// Remove the Role and Rules from the properties
				unset( $this->roles[ $roleDetails['name'] ] );
				foreach( $this->getAllRules() as $key=>$val ) {
					if ( $val['role_id'] == $roleDetails['id'] ) {
						unset( $this->rules[ $key ] );
					}
				}
				$this->_cache->delete( 'acl_roles' );
				return true;
			} catch ( Acl_RoleNoExist $e ) {
				// Change the message a bit
				throw new Acl_RoleNoExist( 'unable to delete role "'.$role.'" as it does not exist' );
			}
		}

    	/**
    	 * Creates a new resource
    	 *
    	 * @param string $name
    	 * @return bool
    	 */
    	public function addResource( $name ) {
    		 if ( !preg_match( self::_REGEX_PATTERN, $name ) ) {
    		 	throw new Acl_InvalidName( 'resource names must only contain alphanumeric chars, underscore and hyphen (A-Z, a-z, 0-9, _, -)' );
    		 }
    		 if ( $this->resourceExists( $name ) ) {
    		 	throw new Acl_ResourceAlreadyExists( 'resource "'.$name.'" already exists, unable to add' );
    		 } else {
    		 	// Resource does not exist so create new
    		 	$this->_sql->insert( 'acl_resources', array( 'name' => $name ) );
    		 	$this->resources[ $name ] = array(
												'id'	=> $this->_sql->lastInsertId(),
												'name'	=> $name,
												);
				$this->_cache->delete( 'acl_resources' );
    		 	return true;
    		 }
    	}

    	/**
    	 * Deletes a resource from the SQL table as well as the
    	 * ACL rules associated with it.
    	 *
    	 * @param mixed $resource
    	 * @return bool
    	 */
    	public function deleteResource( $resource ) {
    		try {
    			if ( !is_array( $resource ) ) {
    				$resource = array( $resource );
    			}
    			foreach( $resource as $tmpResource ) {
					$resourceDetails = $this->getResource( $tmpResource );
					$this->_sql->delete( 'acl_resources', array( 'id' => $resourceDetails['id'] ) );
					$this->_sql->delete( 'acl_rules', array( 'resource_id' => $resourceDetails['id'] ) );
					// Remove them from the class property
					unset( $this->resources[ $resourceDetails['name'] ] );
					foreach( $this->getAllRules() as $key=>$rule ) {
						if ( $rule['resource_id'] == $resourceDetails['id'] ) {
							unset( $this->rules[ $key ] );
						}
					}
				}
				$this->_cache->delete( 'acl_rules' );
				$this->_cache->delete( 'acl_resources' );
    			return true;
    		} catch ( Acl_ResourceNoExist $e ) {
    			// Change the message a bit
    			throw new Acl_ResourceNoExist( 'unable to delete resource "'.$tmpResource.'" as it does not exist' );
    		}
    	}

		/**
		 * Checks if a specified role is allowed to access resource
		 *
		 * The role is checked starting at the specified role and for every
		 * parent of that role. IF any of the roles parents have access
		 * to the resource, the role will to.
		 *
		 * @param string|int $resource
		 * @param string|int $role
		 * @param bool $allowRoot	Toggles if root user has access to everything
		 * @return bool
		 */
		public function check( $resource, $role='', $allowRoot=true ) {
			$resource = $this->getResource( $resource );
      		if ( empty( $resource ) ) {
      			return false;
      		} else if ( !trim( $role ) ) {
				$groupDetails = $this->_session->getGroup();
				$role = $groupDetails['role_id'];
			}
			$roleDetails = $this->getRole( $role );
			if (
				$allowRoot &&
				($roleDetails['name'] == 'group_root'
				 || isset( $groupDetails ) && $groupDetails['id'] == UGManager::_ROOT_GID
				)
			) {
				// Root user/group has ultimate access to anything and everything
				return true;
			}
      		$roles = $this->getRoleTree( $role );
      		if ( empty( $roles ) ) {
      			return false;
      		}
			$rules = $this->getAllRules();
			foreach( $roles as $tmpRole ) {
				foreach( $rules as $rule ) {
					if ( $rule['role_id'] == $tmpRole['id'] && $rule['resource_id'] == $resource['id'] ) {
						return !empty( $rule['access'] );
					}
				}
			}
			return false;
		}

		/**
		 * Checks if a role is allowed to multiple resources which can
		 * be checked in 2 modes:
		 *
		 * _MULTI_ONE: Role must have access to at least one of the resources
		 * _MULTI_ALL: Role must have access to all of the resources
		 *
		 * @param array $resources
		 * @param int $mode
		 * @param string|int $role
		 * @param bool $allowRoot	Toggles if root user has access to everything
		 * @return bool
		 */
		public function checkMulti( array $resources, $mode=self::_MULTI_ONE, $role='', $allowRoot=true ) {
			if ( $mode != self::_MULTI_ONE && $mode != self::_MULTI_ALL ) {
				trigger_error( 'Acl::checkMulti() invalid mode. Mode must be a value which equates to Acl::_MULTI_ONE or Acl::_MULTI_ALL', E_USER_NOTICE );
				return false;
			}
			$access = 0;
			foreach( $resources as $resource ) {
				if ( $this->check( $resource, $role, $allowRoot ) ) {
					$access++;
					if ( $mode == self::_MULTI_ONE ) {
						return true;
					}
				} else if ( $mode == self::_MULTI_ALL ) {
					return false;
				}
			}
			if ( $mode == self::_MULTI_ONE && $access === 0 ) {
				return false;
			}
			return true;
		}

		/**
		 * Builds a form for a user to use which will allow him/her/it to alter the rules
		 * for an ACL Resource and Roles.
		 *
		 * If providing multiple resources, you can also provide specific ACL role hints for
		 * default selection.
		 *
		 * A prefix can be set which will limit which Roles should be shown within the form,
		 * by default - it is anything that begins with 'group_'
		 *
		 * @param mixed $resource
		 * @param string $prefix
		 * @return string|bool
		 */
		public function buildForm( $resource, $prefix='group_' ) {
			/**
			 * Get the role tree for the guest group/role, so that better defaults can be
			 * set for the checkboxes, each role it inherits will be checked.
			 */
			$guestGroup = $this->_ugmanager->getGroup( UGManager::_GUEST_GID );
			$roleHint = array();
			foreach( $this->getRoleTree( $guestGroup['role_id'], true ) as $tmpRole ) {
				$roleHint[] = $tmpRole['id'];
			}
			$rootRole = $this->getRole( 'group_root' );
			$roleHint[] = $rootRole['id']; # Makes root default as well
			// Build the correct array structure for the resources
			$roles = $this->getAllRoles( $prefix ); # Get all of the roles that match the prefix to be used later
			$resources = array();
			foreach( (array) $resource as $name=>$details ) {
				if ( is_array( $details ) ) {
					// We have a provided RESOURCE [0] and ROLE HINT [1]
					$tmpResource = $details[0];
					if ( $this->roleExists( $details[1] ) ) {
						$tmpRoleHint = array( $rootRole['id'] );
						foreach( $this->getRoleTree( $details[1], true ) as $role ) {
							array_unshift( $tmpRoleHint, $role['id'] );
						}
					}
				} else {
					$tmpResource = $details;
				}
				if ( !preg_match( self::_REGEX_PATTERN, $tmpResource ) ) {
				 	trigger_error( 'Acl::buildForm() Resource name must only contain alphanumeric chars, underscore and hyphen (A-Z, a-z, 0-9, _, -), was given "'.$tmpResource.'"' );
				 	return false;
				}
				/**
				 * If the role, check if the roles have access to it which will then
				 * be used later on in the view to provided if the checkbox should be checked
				 */
				$roleAccess = array();
				foreach( $roles as $role ) {
					try {
						$role['access'] = (bool) $this->_input->post( 'acl_resources/'.$tmpResource.'/'.$role['name'] );
					} catch ( Input_KeyNoExist $e ) {
						try {
							$role['access'] = $this->check( $tmpResource, $role['name'], false );
						} catch ( Acl_ResourceNoExist $e ) {
							$role['access'] = in_array( $role['id'], (isset($tmpRoleHint) ? $tmpRoleHint : $roleHint) );
						}
					}
					$role['short_name'] = zula_substr( $role['name'], strlen($prefix) );
					$roleAccess[] = $role;
				}
				$resources[] = array(
									'title' => is_int( $name ) ? $tmpResource : $name,
									'name'	=> $tmpResource,
									'roles'	=> $roleAccess,
									);
			}
			if ( Registry::has( 'theme' ) ) {
				$this->_theme->addJsFile( 'general.js' );
			}
			// Construct the main view file
			$view = new View( 'acl_form.html' );
			$view->assign( array(
								'resources'	=> $resources,
								'roles'		=> $roleAccess,
								));
			return $view->getOutput();
		}

		/**
		 * Updates the ACL Rules for a single resource so that the roles
		 * provided are the *only* ones that have access to the provided
		 * resource (Within the Role Domain provided).
		 *
		 * A Role Domain is basically a role that begins with the provided
		 * string and can restrict on what Roles will be denied access if
		 * they are not in the provided '$roles' array. Hum, maybe an example
		 * will be better to explain:
		 *
		 * EG - If 2 Roles are provided for a resource; 'group_foo' and 'group_bar'
		 * and there is also another Role (not provided in the '$roles' array)
		 * named 'user_fred', then even if the Role 'user_fred' has access to the
		 * provided role, the Rule for 'user_fred' wont change if the Role Domain
		 * is set to 'group_' since the Role 'user_fred' does not begin with 'group_'
		 *
		 * That proabably made *NO* sense what so ever, as I fail at explaining.
		 *
		 * If the resource does not exist, then it will be created first
		 *
		 * @param string $resource
		 * @param array $roles
		 * @param string $roleDomain
		 * @return bool
		 */
		public function allowOnly( $resource, $roles=array(), $roleDomain='group_' ) {
			if ( empty( $roles ) ) {
				$roles = array('group_root');
			}
			$roles = array_flip( $roles );
			foreach( $this->getAllRoles( $roleDomain ) as $role ) {
				$access = isset( $roles[ $role['name'] ] );
				switch( $access ) {
					case true:
						$this->allow( $resource, $role['name'] );
						break;

					case false:
						$this->deny( $resource, $role['name'] );
						break;

					default:
						trigger_error( 'Acl::allowOnly() invalid access for role "'.$role['name'].'" with resource "'.$resource.'". Value must be a bool, reverting to false', E_USER_NOTICE );
						$this->deny( $resource, $role['name'] );
				}
			}
			return true;
		}

		/**
		 * Update the ACL Rules to *allow* a role permission to the
		 * specified resource.
		 *
		 * If the resource does not exist, it will be created first.
		 *
		 * @param string|int $resource
		 * @param string|int $role
		 * @return bool
		 */
		public function allow( $resource, $role ) {
			return $this->updateRules( $resource, $role, true );
		}

		/**
		 * Update the ACL Rules to *deny* a role permission to the
		 * specified resource.
		 *
		 * If the resource does not exist, it will be created first.
		 *
		 * @param string|int $resource
		 * @param string|int $role
		 * @return bool
		 */
		public function deny( $resource, $role ) {
			return $this->updateRules( $resource, $role, false );
		}

		/**
		 * Updates all needed ACL Rules for the provided Resource and Role
		 * with the correct Access provided.
		 *
		 * If the resource does not exist, it will be created first.
		 *
		 * @param string|int $resource
		 * @param string|int $role
		 * @param bool|int $access
		 * @return bool
		 */
		protected function updateRules( $resource, $role, $access ) {
			$access = (int) $access;
			try {
				$resourceDetails = $this->getResource( $resource );
			} catch ( Acl_ResourceNoExist $e ) {
				$this->addResource( $resource );
				$resourceDetails = $this->getResource( $resource );
			}
			$roleDetails = $this->getRole( $role );
			// Get role details andc heck if the Role already has access or not to the resource
			if (
				($access && $this->check( $resource, $role, false ))
				||
				($access == false && !$this->check( $resource, $role, false ))
			) {
				return true;
			} else {
				$details = array(
								'role_id'		=> $roleDetails['id'],
								'resource_id'	=> $resourceDetails['id'],
								);
				if ( $this->ruleExists( $resourceDetails['id'], $roleDetails['id'], !$access ) ) {
					if ( $access == false ) {
						// Remove the rule as there is currently no need for it
						$details['access'] = !$access;
						$this->_sql->delete( 'acl_rules', $details );
					} else {
						// Update the rule with the new access value
						$this->_sql->update( 'acl_rules', array( 'access' => $access ), $details );
					}
					foreach( $this->rules as $key=>$rule ) {
						if (
							$details['role_id'] == $rule['role_id'] && $details['resource_id'] == $rule['resource_id']
							&& $rule['access'] == !$access
						) {
							if ( $access == false ) {
								unset( $this->rules[ $key ] );
							} else {
								$this->rules[ $key ]['access'] = $access;
							}
						}
					}
				} else {
					$details['access'] = $access;
					$this->_sql->insert( 'acl_rules', $details );
					// Add the new Rule to the main class property
					$details['id'] = $this->_sql->lastInsertId();
					$this->rules[ $details['id'] ] = $details;
				}
				/**
				 * Get the roles children to see if any of them have the same ACL Rule
				 * if so, then we just need to remove it, as it's not needed.
				 */
				$roleChildren = $this->getRoleTree( $roleDetails['id'], true );
				array_shift( $roleChildren );
				foreach( $roleChildren as $child ) {
					$ruleId = $this->ruleExists( $resourceDetails['id'], $child['id'], $access );
					if ( $ruleId ) {
						$tmpDetails = array(
											'role_id'		=> $child['id'],
											'resource_id'	=> $resourceDetails['id'],
											'access'		=> $access,
											);
						 $this->_sql->delete( 'acl_rules', $tmpDetails );
						 unset( $this->rules[ $ruleId ] );
					}
				}
				$this->_cache->delete( 'acl_rules' );
				return true;
			}
		}

		/**
		 * Checks if an ACL Rule exists with the provided details and
		 * will return the RuleID if it does exist.
		 *
		 * @param int $resource
		 * @param int $role
		 * @param int $access
		 * @return bool|int
		 */
		protected function ruleExists( $resource, $role, $access ) {
			$where = array(
							'role_id'		=> $role,
							'resource_id'	=> $resource,
							'access'		=> $access,
							);
			$query = $this->_sql->select( 'acl_rules', $where, array( 'id' ) );
			$roleId = $query->fetchColumn();
			return empty($roleId) ? false : $roleId;
		}

	}

?>
