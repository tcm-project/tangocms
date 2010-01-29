<?php

/**
 * Zula Framework Theme Layout
 * --- Provides a way to read a layout map, and get all sectors available for a
 * theme. Ability exists to edit both of these XML files.
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Theme
 */

	class Theme_layout extends Zula_LibraryBase {

		/**
		 * Name of layout currently in use
		 * @var string
		 */
		protected $layoutName = null;

		/**
		 * URL Regex that this layout uses
		 * @var string
		 */
		protected $urlRegex = null;

		/**
		 * States whether this layout is a site type default
		 * @var bool
		 */
		protected $isDefault = false;

		/**
		 * Path to layout XML file to be used
		 * @var string
		 */
		protected $layoutFile = null;

		/**
		 * DomDocumet objects of the sector map and sector file
		 * @var array
		 */
		protected $domObjs = array();

		/**
		 * All the controllers in the current layout file (only
		 * populated once Theme::getControllers() is called)
		 * @var array
		 */
		protected $controllers = array();

		/**
		 * Name of the current theme
		 * @var string
		 */
		protected $themeName = null;

		/**
		 * Paths to the sectors.xml file of the theme
		 * @var string
		 */
		protected $sectorsFile = null;

		/**
		 * All the sectors for the theme (only populated once
		 * Theme::getSectors() is called
		 * @var array
		 */
		protected $sectors = array();

		/**
		 * Constructor
		 * Attempts to load the correct layout XML file, from the name provide.
		 *
		 * If a layout name is given and does not exist, it will attempt to create
		 * it and the needed files. However if no layout name is given, it will try
		 * to find what layout to use, based upon regex against the current URL
		 *
		 * @param string $layoutName
		 * @param string $themeName
		 * @return object
		 */
		public function __construct( $layoutName=null, $themeName=null ) {
			if ( $layoutName == null && _APP_MODE == 'installation' ) {
				$layoutName = 'zula-install-layout.xml';
			} else if ( Registry::has( 'sql' ) ) {
				if ( $layoutName == null ) {
					// Find out what the correct layout should be
					$siteType = $this->_router->getSiteType();
					$pdoSt = $this->_sql->prepare( 'SELECT name, regex FROM {SQL_PREFIX}layouts WHERE
													name LIKE ? AND ? REGEXP regex LIMIT 1' );
					$data = array( $siteType.'%', trim($this->_router->getRawRequestPath(), '/') );
					$pdoSt->execute( $data );
					if ( $row = $pdoSt->fetchAll( PDO::FETCH_ASSOC ) ) {
						$layoutName = $row[0]['name'];
						$this->urlRegex = $row[0]['regex'];
					} else {
						$layoutName = $siteType.'-default';
					}
				} else {
					// 'layouts' table implemented in 2.2.51, gets the regex for the provided layout.
					$pdoSt = $this->_sql->prepare( 'SELECT regex FROM {SQL_PREFIX}layouts WHERE name = ?' );
					$pdoSt->execute( array($layoutName) );
					if ( $regex = $pdoSt->fetchColumn() ) {
						$this->urlRegex = $regex;
					}
				}
			}
			if ( pathinfo( $layoutName, PATHINFO_EXTENSION ) ) {
				$this->layoutFile = $layoutName;
				$this->layoutName = pathinfo( $layoutName, PATHINFO_FILENAME );
			} else {
				$this->layoutFile = $this->_zula->getDir( 'config' ).'/layouts/'.$layoutName.'.xml';
				$this->layoutName = $layoutName;
			}
			foreach( $this->_router->getSiteTypes() as $siteType ) {
				if ( $siteType.'-default' == $layoutName ) {
					$this->isDefault = true;
					break;
				}
			}
			// Set the sectors file from the theme if available
			if ( $themeName != null ) {
				$this->sectorsFile = $this->_zula->getDir( 'themes' ).'/'.$themeName.'/sectors.xml';
				$this->themeName = $themeName;
				if ( !is_file( $this->sectorsFile ) ) {
					throw new Theme_Layout_NoSectorMap( $this->sectorsName.'" does not exist or is not readable' );
				}
			}
		}

		/**
		 * Allows access to the DomDocument objects, this is used to help
		 * implement caching nicely, since the DomDocuments may not need
		 * to be used at all, so less processing.
		 *
		 * @param string $name
		 * @return object
		 */
		public function __get( $name ) {
			if ( $name == 'xmlMap' || $name == 'xmlSectors' ) {
				if ( !isset( $this->domObjs[ $name ] ) ) {
					$this->domObjs[ $name ] = new DomDocument( '1.0', 'UTF-8' );
					$this->domObjs[ $name ]->preserveWhiteSpace = false;
					$this->domObjs[ $name ]->formatOutput = true;
					// Check if to load a file, or create a new document
					$file = $name == 'xmlMap' ? $this->layoutFile : $this->sectorsFile;
					if ( is_file( $file ) ) {
						$this->domObjs[ $name ]->load( $file );
					} else {
						$element = $this->domObjs[ $name ]->createElement( ($name == 'xmlMap' ? 'controllers' : 'sectors') );
						$this->domObjs[ $name ]->appendChild( $element );
					}
				}
				return $this->domObjs[ $name ];
			} else {
				return parent::__get( $name );
			}
		}

		/**
		 * Get all available layouts (can limit by site type, to)
		 *
		 * @param string $siteType
		 * @return array|bool
		 */
		static public function getAll( $siteType=null ) {
			$query = 'SELECT * FROM {SQL_PREFIX}layouts';
			if ( $siteType == null ) {
				$defaultLayouts = array();
				foreach( Registry::get( 'router' )->getSiteTypes() as $tmpSiteType ) {
					$defaultLayouts[] = array('name' => $tmpSiteType.'-default', 'regex' => '');
				}
			} else {
				if ( !Registry::get( 'router' )->siteTypeExists( $siteType ) ) {
					return false;
				}
				$query .= ' WHERE name LIKE "'.$siteType.'-%"';
				$defaultLayouts = array( array('name' => $siteType.'-default', 'regex' => '') );
			}
			// Gather all results from SQL, and ensure the correct file exists
			$pdoQuery = Registry::get( 'sql' )->query( $query.' ORDER BY name' );
			$layoutsDir = Registry::get( 'zula' )->getDir( 'config' ).'/layouts';
			$layouts = array();
			foreach( array_merge( $defaultLayouts, $pdoQuery->fetchAll( PDO::FETCH_ASSOC ) ) as $layout ) {
				if ( file_exists( $layoutsDir.'/'.$layout['name'].'.xml' ) ) {
					$layouts[] = $layout;
				}
			}
			return $layouts;
		}

		/**
		 * Sets the URL regex to be used for this layout (updates database table
		 *
		 * @param string $regex
		 * @return bool
		 */
		public function setUrlRegex( $regex ) {
			$this->urlRegex = $regex;
			return true;
		}

		/**
		 * Gets the URL regex used for this layout
		 *
		 * @return string
		 */
		public function getUrlRegex() {
			return (string) $this->urlRegex;
		}

		/**
		 * Gets the name of the layout
		 *
		 * @return string
		 */
		public function getName() {
			return $this->layoutName;
		}
		
		/**
		 * Sets the name of the layout
		 *
		 * @param string $name
		 * @return bool
		 */
		public function setName( $name ) {
			$this->layoutName = $name;
			$default = false;;
			foreach( $this->_router->getSiteTypes() as $siteType ) {
				if ( $siteType.'-default' == $this->layoutName ) {
					$default = true;
					break;
				}
			}
			$this->isDefault = $default;
			return true;
		}

		/**
		 * Returns true if this layout is a site type default
		 *
		 * @return bool
		 */
		public function isDefault() {
			return $this->isDefault;
		}

		/**
		 * Clears all cache associated with this Layout/theme
		 *
		 * @return bool
		 */
		protected function clearCache() {
			$this->_cache->delete( array(
										'theme_layout_'.$this->themeName.'_'.$this->layoutName,
										'theme_sectors_'.$this->themeName.'_'.$this->layoutName,
										'theme_controllers_'.$this->layoutName,
										)
								 );
			$this->controllers = array();
			$this->getControllers();
			return true;
		}

		/**
		 * Returns a multidimensional array describing the themes layout.
		 * Each index will be that of a SectorID, containing an array of
		 * all the controllers attached to it.
		 *
		 * @return array
		 */
		public function asArray() {
			$cacheKey = 'theme_layout_'.$this->themeName.'_'.$this->layoutName;
			if ( !$layout = $this->_cache->get( $cacheKey ) ) {
				$layout = array();
				foreach( $this->getSectors() as $sector ) {
					$sector['controllers'] = $this->getControllers( $sector['id'] );
					$layout[ $sector['id'] ] = $sector;
				}
				$this->_cache->add( $cacheKey, $layout );
			}
			return $layout;
		}

		/**
		 * Gets every sector in the sector.xml file of the current theme.
		 *
		 * @return array
		 */
		public function getSectors() {
			if ( $this->sectorsFile === null ) {
				throw new Theme_Layout_NoSectorMap( 'unable to get sectors, no sector map loaded' );
			} else if ( empty( $this->sectors ) ) {
				// Attempt to get the sectors from cache
				$cacheKey = 'theme_sectors_'.$this->themeName.'_'.$this->layoutName;
				if ( !$this->sectors = $this->_cache->get( $cacheKey ) ) {
					if ( $this->isDefault() || $this->layoutName == 'zula-install-layout' ) {
						// Layout is a site type default, so add in the SC sector
						$this->sectors = array(
											'SC' => array(
														'id' 			=> 'SC',
														'description' 	=> t('Requested Module', Locale::_DTD),
														),
											);
					} else {
						$this->sectors = array();
					}
					foreach( $this->xmlSectors->getElementsByTagName( 'sector' ) as $node ) {
						$id = strtoupper( $node->getAttribute( 'id' ) );
						$this->sectors[ $id ] = array(
													'description' 	=> $node->getElementsByTagName( 'description' )->item(0)->nodeValue,
													'id'			=> $id,
													);
					}
					$this->_cache->add( $cacheKey, $this->sectors );
				}
			}
			return $this->sectors;
		}

		/**
		 * Checks if a sector exists by ID
		 *
		 * @param string $id
		 * @return bool
		 */
		public function sectorExists( $id ) {
			return array_key_exists( strtoupper($id), $this->getSectors() );
		}

		/**
		 * Gather information about a sector
		 *
		 * @param int $id
		 * @return array
		 */
		public function getSectorDetails( $id ) {
			if ( $this->sectorExists( $id ) ) {
				return $this->sectors[ strtoupper($id) ];
			}
			throw new Theme_SectorNoExist( 'details could not be got for sector "'.$id.'" as it does not exist' );
		}

		/**
		 * Gets all controllers currently in the layout file. If a
		 * sector is specified, then only those attached to that sector
		 * will be returned.
		 *
		 * @param string $inSector
		 * @return array
		 */
		public function getControllers( $inSector=null ) {
			if ( empty( $this->controllers ) ) {
				$cacheKey = 'theme_controllers_'.$this->layoutName;
				if ( !$this->controllers = $this->_cache->get( $cacheKey ) ) {
					$this->controllers = array();
					foreach( $this->xmlMap->getElementsByTagName( 'controller' ) as $node ) {
						$config = array();
						foreach( $node->getElementsByTagName( 'config' )->item(0)->childNodes as $confChild ) {
							// Gather all configuration values
							if ( $confChild->nodeType == XML_ELEMENT_NODE ) {
								$config[ $confChild->nodeName ] = $confChild->nodeValue;
							}
						}
						if ( !isset( $config['displayTitle'] ) ) {
							$config['displayTitle'] = true;
						}
						if ( !isset( $config['customTitle'] ) ) {
							$config['customTitle'] = null;
						}
						if ( !isset( $config['htmlWrapClass'] ) ) {
							$config['htmlWrapClass'] = null;
						}
						// 2.3.51 changed attr 'for_sector' into 'sector', use older if exists
						if ( ($sector = $node->getAttribute( 'for_sector' )) == false ) {
							$sector = $node->getAttribute( 'sector' );
						}
						$cid = $node->getAttribute( 'id' );
						$this->controllers[ $cid ] = array(
														'id'		=> $cid,
														'order'		=> (int) $node->getAttribute( 'order' ),
														'sector'	=> strtoupper( $sector ),
														'mod' 		=> $node->getElementsByTagName( 'mod' )->item(0)->nodeValue,
														'con'		=> $node->getElementsByTagName( 'con' )->item(0)->nodeValue,
														'sec'		=> $node->getElementsByTagName( 'sec' )->item(0)->nodeValue,
														'config'	=> $config,
														);
					}
					zula_normalize( $this->controllers );
					uasort( $this->controllers, array($this, 'orderControllers') );
					$this->_cache->add( $cacheKey, $this->controllers );
				}
			}
			if ( $inSector == null ) {
				return $this->controllers;
			} else {
				$inSector = strtoupper( $inSector );
				$controllers = array();
				foreach( $this->controllers as $controller ) {
					if ( $controller['sector'] == $inSector ) {
						$controllers[ $controller['id'] ] = $controller;
					}
				}
				return $controllers;
			}
		}

		/**
		 * Sorts the sector controllers array by the order set
		 *
		 * @param array $a
		 * @param array $b
		 * @return int
		 */
		protected function orderControllers( $a, $b ) {
			return strcmp( $a['order'], $b['order'] );
		}

		/**
		 * Checks if a controller exists by ID
		 *
		 * @param int $id
		 * @return bool
		 */
		public function controllerExists( $id ) {
			return array_key_exists( $id, $this->getControllers() );
		}

		/**
		 * Gathers and returns details for a controller by ID.
		 *
		 * @param int $id
		 * @return array
		 */
		public function getControllerDetails( $id ) {
			if ( $this->controllerExists( $id ) ) {
				return $this->controllers[ $id ];
			}
			throw new Theme_Layout_ControllerNoExist( 'unable to get details for controller "'.$id.'" as it does not exist' );
		}

		/**
		 * Creates a unique ID to be used for controllers
		 *
		 * @return string
		 */
		public function makeCntrlrUid() {
			$controllers = $this->getControllers();
			do {
				$id = substr( uniqid( rand() ), 0, 3 );
			} while( array_key_exists( $id, $controllers ) );
			return $id;
		}

		/**
		 * Adds a new controller to the sector map XML file. Once added
		 * it will return the unique ID of the controller.
		 *
		 * @param string $sector
		 * @param array $details
		 * @param int $id
		 * @return int
		 */
		public function addController( $sector, array $details, $id=null ) {
			$defaults = array(
							'mod'		=> 'index',
							'con' 		=> 'index',
							'sec' 		=> 'index',
							'config'	=> array('displayTitle' => true, 'customTitle' => ''),
							'order'		=> null,
							);
			$details = zula_merge_recursive( $defaults, $details );
			$cntrlrElement = $this->xmlMap->createElement( 'controller' );
			$attributes = array(
								'id' 		=> $id == null ? $this->makeCntrlrUid() : $id,
								'sector'	=> $sector,
								'order'		=> ctype_digit( (string) $details['order']) ? $details['order'] : null,
								);
			// Attach all of the attributes
			foreach( $attributes as $key=>$val ) {
				$attrib = $this->xmlMap->createAttribute( $key );
				$attrib->appendChild( $this->xmlMap->createTextNode( $val ) );
				$cntrlrElement->appendChild( $attrib );
			}
			// Add all of the elements for this controller.
			$this->attachElement( $cntrlrElement,
								  array('mod'		=> $details['mod'],
										'con'		=> $details['con'],
										'sec'		=> $details['sec'],
										'config'	=> $details['config'])
								);
			$this->xmlMap->documentElement->appendChild( $cntrlrElement );
			// Add in ACL resource with default permissions of guest inheritence tree
			$groupDetails = $this->_ugmanager->getGroup( Ugmanager::_GUEST_GID );
			$roles = array('group_root');
			foreach( $this->_acl->getRoleTree( $groupDetails['role_id'], true ) as $tmpRole ) {
				$roles[] = $tmpRole['name'];
			}
			$this->_acl->allowOnly( 'layout_controller_'.$attributes['id'], $roles );
			// Remove cache entries
			$this->clearCache();
			Hooks::notifyAll( 'layout_add_cntrlr', $attributes['id'], $details );
			return $attributes['id'];
		}

		/**
		 * Helper method for when adding a new controller. Takes an array
		 * of text nodes that need to be created, and will recursively
		 * create them, attaching them to the element.
		 *
		 * @param object $element
		 * @param array $textNodes
		 * @return bool
		 */
		protected function attachElement( DomElement &$element, array $textNodes ) {
			foreach( $textNodes as $key=>$val ) {
				$tmpElement = $this->xmlMap->createElement( $key );
				if ( is_array( $val ) ) {
					$this->attachElement( $tmpElement, $val );
				} else {
					$method = zula_needs_cdata($val) ? 'createCDATASection' : 'createTextNode';
					$tmpElement->appendChild( $this->xmlMap->$method( $val ) );
				}
				$element->appendChild( $tmpElement );
			}
			return true;
		}

		/**
		 * Removes a controller from the sector map, by ID
		 *
		 * @param int $id
		 * @return bool
		 */
		public function detachController( $id ) {
			if ( !$this->controllerExists( $id ) ) {
				throw new Theme_Layout_ControllerNoExist;
			}
			$controllerNodes = $this->xmlMap->getElementsByTagName( 'controller' );
			for( $i = 0; $i < $controllerNodes->length; $i++ ) {
				if ( $controllerNodes->item( $i )->getAttribute( 'id' ) == $id ) {
					$this->xmlMap->documentElement->removeChild( $controllerNodes->item( $i ) );
					// Remove cache entries
					$this->clearCache();
					Hooks::notifyAll( 'layout_detach_cntrlr', $id );
					return true;
				}
			}
			return false;
		}

		/**
		 * Edit a controller that is already in the sector map. This is done easily
		 * by first removing the controller, then adding a new one (with the same ID)
		 *
		 * @param int $id
		 * @param array $details
		 * @return bool
		 */
		public function editController( $id, array $details ) {
			$cntrlr = $this->getControllerDetails( $id );
			$details['mod'] = $cntrlr['mod'];
			if ( empty( $details['sector'] ) ) {
				$details['sector'] = $cntrlr['sector'];
			}
			if ( $this->detachController( $id ) ) {
				$this->addController( $details['sector'], $details, $id );
				Hooks::notifyAll( 'layout_edit_cntrlr', $id, $details );
				return $id;
			} else {
				return false;
			}
		}

		/**
		 * Save the layout file and updates/inserts SQL entry if needed
		 *
		 * @param string $path
		 * @return bool
		 */
		public function save( $path=null ) {
			$path = trim($path) ? $path : $this->layoutFile;
			if (
				(file_exists( $path ) && zula_is_writable( $path ) || !file_exists( $path ) && zula_is_writable( dirname($path) ))
				&& $this->xmlMap->save( $path )
			) {
				if ( $this->isDefault() === false && Registry::has( 'sql' ) ) {
					$pdoSt = $this->_sql->prepare( 'INSERT INTO {SQL_PREFIX}layouts (name, regex) VALUES (?, ?)
													ON DUPLICATE KEY UPDATE regex = VALUES(regex)' );
					$pdoSt->execute( array($this->layoutName, $this->getUrlRegex()) );
				}
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Deletes the layout file and any SQL entry
		 *
		 * @return bool
		 */
		public function delete() {
			if ( zula_is_deletable( $this->layoutFile ) && unlink( $this->layoutFile ) ) {
				$pdoSt = $this->_sql->prepare( 'DELETE FROM {SQL_PREFIX}layouts WHERE name = ?' );
				$pdoSt->execute( array($this->layoutName) );
				return true;
			} else {
				return false;
			}
		}

	}

?>
