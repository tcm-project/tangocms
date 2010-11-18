<?php

/**
 * Zula Framework Modules
 * --- Provides a way to easily work with modules
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Module
 */

	class Module extends Zula_LibraryBase {

		/**
		 * Constants used when getting all modules, to toggle what type
		 */
		const
				_INSTALLED	= 1,
				_INSTALLABLE= 2,
				_ALL		= 3,

				_SORT_ALPHA	= 1,
				_SORT_ORDER	= 2;

		/**
		 * Directory that the modules are in
		 * @var string
		 */
		static protected $moduleDir = null;

		/**
		 * SQL Details of modules, such as Disabled status and Load Order
		 * @var array
		 */
		static protected $sqlModules = null;

		/**
		 * Holds name of modules that are known to exist
		 * @var array
		 */
		static protected $modules = array();

		/**
		 * Stores the name of every disabled module
		 * @var array
		 */
		static protected $disabledModules = null;

		/**
		 * Current loading MCS (Module, Controller, Section) details
		 * @var array|bool
		 */
		static private $currentMcs = false;

		/**
		 * Holds the current loading controller object
		 * @var object
		 */
		static protected $currentCntrlrObj = false;

		/**
		 * Name of the module requested
		 * @var string
		 */
		protected $modName = null;

		/**
		 * Path to the current module (no trailing slash)
		 * @var string
		 */
		protected $path = null;

		/**
		 * Details about the module
		 * @var array
		 */
		protected $details = null;

		/**
		 * Constructor
		 * Checks if the provided module does actaully exist then gets
		 * the details for it.
		 *
		 * @param string $module
		 * @return object
		 */
		public function __construct( $module ) {
			if ( self::exists( $module, true ) ) {
				$this->modName = $module;
				$this->path = self::$moduleDir.'/'.$module;
				if ( $this->getDetails( $module ) === false ) {
					throw new Module_NoExist( 'no modules could be got for module "'.$module.'", ensure details.xml file exists' );
				}
			} else {
				throw new Module_NoExist;
			}
		}

		/**
		 * Allows for quick access to module details as properties
		 *
		 * @param string $var
		 * @return mixed
		 */
		public function __get( $var ) {
			return isset($this->details[ $var ]) ? $this->details[ $var ] : parent::__get( $var );
		}

		/**
		 * Gets the current module dir in use
		 *
		 * @return string
		 */
		static public function getDirectory() {
			return self::$moduleDir;
		}

		/**
		 * Sets where to look for modules
		 *
		 * @param string $dir
		 * @return bool
		 */
		static public function setDirectory( $dir ) {
			self::$moduleDir = $dir;
			return true;
		}

		/**
		 * Checks if a module exists. For a module to exist, the details.xml file
		 * must be present in the module dir.
		 *
		 * @param string $module
		 * @param bool $checkInstallable	Check installable modules as well
		 * @return bool
		 */
		static public function exists( $module, $checkInstallable=false ) {
			return in_array( $module, self::getModules( ($checkInstallable ? self::_ALL : self::_INSTALLED), true ) );
		}

		/**
		 * Checks if a module is disabled
		 *
		 * @param string $module
		 * @return bool
		 */
		static public function isDisabled( $module ) {
			return in_array( $module, self::getDisabledModules() );
		}

		/**
		 * Scans the modules directory to find all of the modules of a given
		 * type (either Installed, Installable or Both)
		 *
		 * @param int $type
		 * @param bool $disabled	Get disabled modules aswell
		 * @param int $order
		 * @return array
		 */
		static public function getModules( $type=self::_INSTALLED, $disabled=false, $order=self::_SORT_ALPHA ) {
			if ( $type == self::_ALL ) {
				return array_merge(
									self::getModules( self::_INSTALLED, $disabled, $order ),
									self::getModules( self::_INSTALLABLE, false, $order )
								  );
			} else if ( $type == self::_INSTALLED ) {
				$typeKey = 'installed';
			} else if ( $type == self::_INSTALLABLE ) {
				$typeKey = 'installable';
			}
			if ( self::$sqlModules === null && Registry::has( 'sql' ) && Registry::get('zula')->getState() != 'setup' ) {
				$query = 'SELECT * FROM {PREFIX}modules ORDER BY `order`, name';
				foreach( Registry::get('sql')->query( $query, PDO::FETCH_ASSOC ) as $row ) {
					self::$sqlModules[ $row['name'] ] = $row;
				}
			}
			/**
			 * Gather all modules for the specified type and directory
			 */
			if ( isset( self::$modules[ self::getDirectory() ][ $typeKey ] ) ) {
				$modules = self::$modules[ self::getDirectory() ][ $typeKey ];
			} else {
				$modules = array();
				foreach( new DirectoryIterator( self::getDirectory() ) as $file ) {
					$resource = $file.'_global';
					$moduleName = $file->getFilename();
					$modulesDir = $file->getPath().'/'.$moduleName;
					if ( strpos( $file, '.' ) === 0 || !file_exists( $modulesDir.'/details.xml' ) ) {
						continue;
					} else if ( $type == self::_INSTALLABLE ) {
						$installPath = $modulesDir.'/install.xml';
						if (
							file_exists( $installPath ) &&
							Registry::has('acl') && !Registry::get('acl')->resourceExists( $resource )
						) {
							$modules[] = $moduleName;
						}
					} else if ( _ACL_ENABLED === false ) {
						$modules[] = $moduleName;
					} else if ( Registry::get('acl')->resourceExists($resource) && isset(self::$sqlModules[ $moduleName ]) ) {
						$modules[] = $moduleName;
					}
				}
				sort( $modules );
				self::$modules[ self::getDirectory() ][ $typeKey ] = $modules;
			}
			/**
			 * Prepare the modules be removing the disabled ones, if needed and also
			 * sort them in the correct way to be returned.
			 */
			if ( $disabled == false && $type == self::_INSTALLED ) {
				foreach( $modules as $key=>$val ) {
					if ( self::isDisabled( $val ) ) {
						unset( $modules[ $key ] );
					}
				}
			}
			if ( $type == self::_INSTALLABLE || $order == self::_SORT_ALPHA || empty( self::$sqlModules ) ) {
				sort( $modules );
				return $modules;
			} else {
				// Order by the Module Load Order (order key)
				return array_intersect( array_keys(self::$sqlModules), $modules );
			}
		}

		/**
		 * Gets all disabled modules either from SQL or the stored array
		 *
		 * @return array
		 */
		static public function getDisabledModules() {
			if ( is_null( self::$disabledModules ) ) {
				if ( !Registry::has( 'sql' ) ) {
					return array();
				}
				self::$disabledModules = Registry::get( 'sql' )->query('SELECT name FROM {PREFIX}modules WHERE disabled = 1')
															   ->fetchAll( PDO::FETCH_COLUMN );
			}
			return self::$disabledModules;
		}

		/**
		 * Gets the MCS (Module, Controller, Section) for module that is
		 * currently being loaded. Will return bool false if none is being
		 * loaded.
		 *
		 * @param string $key
		 * @return mixed
		 */
		static public function getLoading( $key=null ) {
			if ( self::$currentMcs === false ) {
				return false;
			} else if ( trim( $key ) && isset( self::$currentMcs[ $key ] ) ) {
				return self::$currentMcs[ $key ];
			}
			return self::$currentMcs;
		}

		/**
		 * Gets the current loaded/loading module controller object
		 *
		 * @return object|bool
		 */
		static public function getLoadingObj() {
			return self::$currentCntrlrObj;
		}

		/**
		 * Gets all of the general details for the module
		 *
		 * @return array|bool
		 */
		public function getDetails() {
			if ( $this->details === null ) {
				$cacheKey = 'mod_details_'.$this->modName;
				if ( ($this->details = $this->_cache->get($cacheKey)) == false ) {
					// Gather details from the details.xml file
					if ( is_readable( $this->path.'/details.xml' ) ) {
						$dom = new DomDocument;
						$dom->load( $this->path.'/details.xml' );
						foreach( $dom->getElementsByTagName('detail')->item(0)->getElementsByTagName('*') as $item ) {
							$this->details[ $item->nodeName ] = $item->nodeValue;
						}
						$this->details['disabled'] = self::isDisabled( $this->details['name'] );
						$this->_cache->add( $cacheKey, $this->details );
					} else {
						return false;
					}
				}
			}
			return $this->details;
		}

		/**
		 * Enables the module if it is not already enabled
		 *
		 * @return bool
		 */
		public function enable() {
			if ( self::isDisabled( $this->name ) ) {
				$pdoSt = $this->_sql->prepare( 'UPDATE {PREFIX}modules SET disabled = 0 WHERE name = ?' );
				$pdoSt->execute( array($this->name) );
				if ( $pdoSt->rowCount() > 0 ) {
					// Remove the key from the main disabled_modules property
					while ( ($key = array_search( $this->name, self::$disabledModules )) !== false ) {
						unset( self::$disabledModules[ $key ] );
					}
					$this->_cache->delete( 'mod_details_'.$this->name );
					Hooks::notifyAll( 'module_enable', $this->name );
					return true;
				} else {
					return false;
				}
			} else {
				return true;
			}
		}

		/**
		 * Disables the module
		 *
		 * @return bool
		 */
		public function disable() {
			if ( self::isDisabled( $this->name ) ) {
				return true;
			} else {
				$pdoSt = $this->_sql->prepare( 'UPDATE {PREFIX}modules SET disabled = 1 WHERE name = ?' );
				$pdoSt->execute( array($this->name) );
				if ( $pdoSt->rowCount() > 0 ) {
					self::$disabledModules[] = $this->name;
					$this->_cache->delete( 'mod_details_'.$this->name );
					Hooks::notifyAll( 'module_disable', $this->name );
					return true;
				} else {
					return false;
				}
			}
		}

		/**
		 * Sets the load order for this module
		 *
		 * @param int $order
		 * @return bool
		 */
		public function setLoadOrder( $order ) {
			$pdoSt = $this->_sql->prepare( 'UPDATE {PREFIX}modules SET `order` = ? WHERE name = ?' );
			return $pdoSt->execute( array((int) $order, $this->name) );
		}

		/**
		 * Attempts to install the module, if it is not already installed
		 * Details from a 'install.xml' file specify various rules to follow
		 * when installing, such as which PHP version is required.
		 *
		 * bool false will be returned when the module is not installable, an
		 * array will be returned if the install failed - containing details
		 * of what passed/failed the checks. Bool true will be returned when
		 * everything was installed ok.
		 *
		 * @return bool
		 */
		public function install() {
			if ( in_array( $this->name, self::getModules(self::_INSTALLABLE) ) ) {
				$details = $this->getInstallDetails();
				/**
				 * Generate a results array to confirm that all dependencies are met
				 */
				$results = $details['dependencies'];
				$passed = true;
				foreach( $results as $pkg=>$val ) {
					if ( $pkg == 'php' ) {
						if ( version_compare(PHP_VERSION, $val['version'], $val['operator']) ) {
							foreach( $val['extensions'] as $phpExt ) {
								if ( !extension_loaded( $phpExt ) ) {
									$results[ $pkg ]['passed'] = false;
									break;
								}
							}
						} else {
							$results[ $pkg ]['passed'] = false;
						}
					} else {
						$version = ($pkg == 'zula') ? Zula::_VERSION : _PROJECT_VERSION;
						$results[ $pkg ]['passed'] = version_compare( $version, $val['version'], $val['operator'] );
					}
					if ( $results[ $pkg ]['passed'] === false ) {
						$passed = false;
					}
				}
				if ( $passed === false ) {
					return $results;
				}
				/**
				 * Continue with the installation, firstly doing all SQL queries needed
				 */
				try {
					$this->_sql->loadSqlFile( $this->path, 'install' );
				} catch (Sql_InvalidFile $e) {
					/* Silently fail as an install SQL file is not required for
					a module, and so installation should continue. */
					$this->_log->message( $e->getMessage(), Log::L_NOTICE );
				}
				if ( $this->_sql->getAttribute( PDO::ATTR_DRIVER_NAME ) == 'sqlsrv' ) {
					$stmt = 'MERGE INTO {PREFIX}modules AS dest
							USING (VALUES(:name)) AS src(name)
							ON dest.name = src.name
							WHEN MATCHED THEN
								UPDATE SET name = src.name
							WHEN NOT MATCHED THEN
								INSERT (name) VALUES(src.name);';
				} else {
					$stmt = 'INSERT INTO {PREFIX}modules (name) VALUES(:name)
							ON DUPLICATE KEY UPDATE name=name';
				}
				$pdoSt = $this->_sql->prepare( $stmt );
				$pdoSt->bindValue( ':name', $this->name );
				$pdoSt->execute();
				// Add all of the new ACL resources and run the install.sql file
				$guestGroup = $this->_ugmanager->getGroup( Ugmanager::_GUEST_GID );
				foreach( $details['aclResources'] as $resource=>$roleHint ) {
					$roles = array('group_root');
					if ( $roleHint ) {
						if ( $roleHint == 'guest' ) {
							$roleHint = $guestGroup['role_id'];
						}
						if ( $this->_acl->roleExists( $roleHint ) ) {
							foreach( $this->_acl->getRoleTree( $roleHint, true ) as $role ) {
								array_unshift( $roles, $role['name'] );
							}
						}
					}
					$this->_acl->allowOnly( $resource, $roles );
				}
				foreach( array('sql', 'ini') as $confType ) {
					$lib = '_config_'.$confType;
					foreach( $details['config'][ $confType ] as $key=>$val ) {
						if ( $this->$lib->has( $key ) ) {
							unset( $details['config'][ $confType ][ $key ] );
						}
					}
					$this->$lib->add(
									array_keys( $details['config'][ $confType ] ),
									array_values( $details['config'][ $confType ] )
									);
				}
				$this->_cache->delete( 'mod_details_'.$this->name );
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Parses the module install.xml file to get various
		 * installation details
		 *
		 * @return array
		 */
		protected function getInstallDetails() {
			$file = $this->path.'/install.xml';
			if ( !file_exists( $file ) || !is_readable( $file ) ) {
				throw new Module_NotInstallable( 'installation file "'.$file.'" does not exist' );
			}
			// Default detail array and allowed version operators
			$allowedOperators = array('<', 'lt', '<=', 'le', '>', 'gt', '>=', 'ge', '==', '=', 'eq', '!=', '<>', 'ne');
			$details = array(
							'file'	=> $file,
							'dependencies'	=> array(
													'php'	=> array(
																	'passed'		=> true,
																	'version'		=> '5.2.0',
																	'operator'		=> '>=',
																	'extensions'	=> array(),
																	),
													'zula'	=> array(
																	'passed'		=> true,
																	'version'		=> Zula::_VERSION,
																	'operator'		=> '>=',
																	),
													'tcm'	=> array(
																	'passed'		=> true,
																	'version'		=> _PROJECT_VERSION,
																	'operator'		=> '>='
																	),
													),
							'aclResources'	=> array($this->name.'_global' => null),
							'config'		=> array('sql' => array(), 'ini' => array()),
							);
			// Parse the install.xml file
			$dom = new DomDocument;
			$dom->load( $file );
			$xPath = new DomXpath( $dom );
			foreach( $xPath->query('//dependencies/*') as $node ) {
				if ( isset( $details['dependencies'][ $node->nodeName ] ) ) {
					// Get which version of the dependency is required
					$version = $xPath->query( 'version', $node )->item(0);
					if ( $version instanceof DomElement ) {
						$operator = $version->getAttribute( 'operator' );
						if ( in_array( $operator, $allowedOperators ) ) {
							$details['dependencies'][ $node->nodeName ]['operator'] = $operator;
						}
						$details['dependencies'][ $node->nodeName ]['version'] = $version->nodeValue;
					}
					// Required dependency extensions
					foreach( $xPath->query('extensions/extension', $node) as $extension ) {
						$details['dependencies'][ $node->nodeName ]['extensions'][] = $extension->nodeValue;
					}
				}
			}
			// Additional ACL Resources
			foreach( $xPath->query('//aclResources/resource') as $node ) {
				$details['aclResources'][ $node->nodeValue ] = $node->getAttribute( 'roleHint' );
			}
			// Configuration settings for both SQL and INI
			foreach( $xPath->query('//config/sql/* | //config/ini/*') as $node ) {
				$type = $xPath->query( '..', $node )->item(0)->nodeName;
				if ( ($confKey = $node->getAttribute('key')) ) {
					$details['config'][ $type ][ $confKey ] = $node->nodeValue;
				}
			}
			return $details;
		}

		/**
		 * Checks is a controller (though not a section)
		 *
		 * @param string $cntrlr
		 * @return bool
		 */
		public function controllerExists( $cntrlr ) {
			return is_readable( $this->path.'/controllers/'.$cntrlr.'.php' );
		}

		/**
		 * Loads a specified controller and section if it exists. The resulting
		 * output will be returned, a long with the title set by the controller
		 * as an array.
		 *
		 * @param string $cntrlr
		 * @param string $sec
		 * @param array $config
		 * @param string $sector
		 * @return array
		 */
		public function loadController( $cntrlr='index', $sec='index', array $config=array(), $sector=null ) {
			$cntrlr = trim($cntrlr) ? $cntrlr : 'index';
			$sec = trim($sec) ? $sec : 'index';
			// Ensure no other controller/module is being loaded currently
			$this->_log->message( sprintf( 'attempting to load controller "%s::%s::%s"', $this->name, $cntrlr, $sec ), Log::L_DEBUG );
			if ( self::$currentMcs !== false ) {
				throw new Module_UnableToLoad( 'unable to load new module, a module is already loading' );
			} else if ( $this->disabled === true ) {
				$lMsg = 'unable to load controller, parent module "'.$this->name.'" is currently disabled';
				$this->_log->message( $lMsg, Log::L_NOTICE );
				throw new Module_Disabled( $lMsg );
			} else if ( _ACL_ENABLED ) {
				$resource = $this->name.'_global';
				if ( !$this->_acl->check( $resource ) ) {
					throw new Module_NoPermission( $resource );
				}
			}
			if ( !$this->controllerExists( $cntrlr ) ) {
				throw new Module_ControllerNoExist( 'controller "'.$cntrlr.'" does not exist' );
			}
			/**
			 * Create some details for the controller, create a new
			 * instance of it and identify it with the details needed.
			 */
			$class = $this->name.'_controller_'.$cntrlr;
			$method = $sec.'Section';
			try {
				self::$currentCntrlrObj = new $class( $this->getDetails(), $config, $sector );
				if ( self::$currentCntrlrObj instanceof Zula_ControllerBase ) {
					if ( !is_callable( array(self::$currentCntrlrObj, $method), false, $callableName ) ) {
						throw new Module_ControllerNoExist( 'controller section/method "'.$callableName.'" is not callable' );
					}
					// Store MCS details
					self::$currentMcs = array(
												'module'	=> $this->name,
												'cntrlr'	=> $cntrlr,
												'section'	=> $sec,
												);
					$details = array(
									'cntrlr'	=> self::$currentCntrlrObj,
									'ident'		=> $this->name.'::'.$cntrlr.'::'.$sec,
									'output'	=> self::$currentCntrlrObj->$method(),
									'outputType'=> self::$currentCntrlrObj->getOutputType(),
									'title'		=> self::$currentCntrlrObj->getTitle(), # This *has* to be below 'output'
									);
					/**
					 * Trigger output hooks. Listeners should return a string with the
					 * html they want to add to the controllers output.
					 */
					if ( !is_bool( $details['output'] ) ) {
						$ota = Hooks::notifyAll( 'module_output_top', self::getLoading(), $details['outputType'], $sector, $details['title'] );
						$outputTop = count($ota) > 0 ? implode( "\n", $ota ) : '';
						$oba = Hooks::notifyAll( 'module_output_bottom', self::getLoading(), $details['outputType'], $sector, $details['title'] );
						$outputBottom = count($oba) > 0 ? implode( "\n", $oba ) : '';
						$details['output'] = $outputTop.$details['output'].$outputBottom;
					}
					Hooks::notifyAll( 'module_controller_loaded', self::getLoading(), $details['outputType'], $sector, $details['title'] );
					// Reset MCS details and restore i18n domain
					self::$currentMcs = false;
					self::$currentCntrlrObj = false;
					$this->_i18n->textDomain( I18n::_DTD );
					return $details;
				} else {
					throw new Module_ControllerNoExist( 'controller "'.$class.'" must extend Zula_ControllerBase' );
				}
			} catch ( Exception $e ) {
				// Catch any exceptions throw to reset the MCS details, then re-throw
				$this->_i18n->textDomain( I18n::_DTD );
				self::$currentMcs = false;
				self::$currentCntrlrObj = false;
				throw $e;
			}
		}

	}

?>
