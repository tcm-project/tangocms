<?php

/**
 * Zula Framework Theme Layout
 * --- Provides a way to read and write a Layout file for controllers
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Theme
 */

	class Layout extends Zula_LibraryBase {

		/**
		 * Name of layout currently in use
		 * @var string
		 */
		protected $name = null;

		/**
		 * Regex that this layout uses
		 * @var string
		 */
		protected $regex = null;

		/**
		 * Path to layout XML file to be used
		 * @var string
		 */
		protected $path = null;

		/**
		 * DomDocumet object of the layouts XML
		 * @var object
		 */
		protected $dom = null;

		/**
		 * Details of all controllers in this layout (only populated
		 * once self::getControllers() is called)
		 * @var array
		 */
		protected $controllers = array();

		/**
		 * Constructor
		 *
		 * Attempts to load the correct layout xml file
		 *
		 * @param string $name
		 * @return object
		 */
		public function __construct( $name=null ) {
			if ( pathinfo( $name, PATHINFO_EXTENSION ) ) {
				$this->path = $name;
				$this->name = pathinfo( $name, PATHINFO_FILENAME );
			} else {
				$this->path = $this->_zula->getDir( 'config' ).'/layouts/'.$name.'.xml';
				$this->name = $name;
			}
			if ( Registry::has( 'sql' ) ) {
				// Find the regex for this layout
				$pdoSt = $this->_sql->prepare( 'SELECT regex FROM {PREFIX}layouts WHERE name = ?' );
				$pdoSt->execute( array($name) );
				if ( $regex = $pdoSt->fetchColumn() ) {
					$this->regex = $regex;
				}
				$pdoSt->closeCursor();
			}
			// Load the DomDocument (or create if needed)
			$this->dom = new DomDocument( '1.0', 'UTF-8' );
			$this->dom->preserveWhiteSpace = false;
			$this->dom->formatOutput = true;
			if ( is_file( $this->path ) ) {
				$this->dom->load( $this->path );
			} else {
				$this->dom->appendChild( $this->dom->createElement('controllers') );
			}
		}

		/**
		 * Takes a site type and a request path, then attempts to find a layout
		 * name for which the regex matches against the request path. If it can't
		 * find a match, then 'sitetype-default' will be returned.
		 *
		 * @param string $siteType
		 * @param string $requestPath
		 * @return string
		 */
		static public function find( $siteType, $requestPath ) {
			$pdoSt = Registry::get('sql')->prepare( 'SELECT name, regex FROM {PREFIX}layouts
													 WHERE name LIKE :name ORDER BY name' );
			$pdoSt->bindvalue( ':name', $siteType.'%' );
			$pdoSt->execute();
			$availableLayouts = $pdoSt->fetchAll( PDO::FETCH_ASSOC );
			foreach( $availableLayouts as $layout ) {
				$regex = addcslashes( $layout['regex'], '#' );
				if ( @preg_match( '#'.$regex.'#', $requestPath ) ) {
					$layoutName = $layout['name'];
					break;
				}
				if ( preg_last_error() != PREG_NO_ERROR ) {
					$this->_log->message( 'Content layout regex is invalid', Log::L_WARNING );
				}
			}
			return isset($layoutName) ? $layoutName : $siteType.'-default';
		}

		/**
		 * Get all available layouts. Can limit by a site type if needed
		 *
		 * @param string $siteType
		 * @return array
		 */
		static public function getAll( $siteType=null ) {
			$layoutDir = Registry::get( 'zula' )->getDir( 'config' ).'/layouts';
			$glob = $siteType ? $siteType.'-*.xml' : '*.xml';
			foreach( glob($layoutDir.'/'.$glob) as $file ) {
				$name = pathinfo( $file, PATHINFO_FILENAME );
				if ( preg_match( '#^(?:admin|main)-default$#i', $name ) ) {
					$layouts[] = array('name' => $name, 'regex' => '');
				} else {
					if ( !isset( $pdoSt ) ) {
						$pdoSt = Registry::get('sql')->prepare( 'SELECT name, regex FROM {PREFIX}layouts WHERE name = ?' );
					}
					$pdoSt->execute( array($name) );
					if ( $row = $pdoSt->fetch(PDO::FETCH_ASSOC) ) {
						$layouts[] = $row;
					}
				}
			}
			if ( isset($pdoSt) ) {
				$pdoSt->closeCursor();
			}
			return $layouts;
		}

		/**
		 * Sorts the controllers array by the order attribute
		 *
		 * @param array $a
		 * @param array $b
		 * @return int
		 */
		protected function orderControllers( array $a, array $b ) {
			return strcmp( $a['order'], $b['order'] );
		}

		/**
		 * Creates a unique ID to be used for controllers
		 *
		 * @return string
		 */
		protected function makeCntrlrUid() {
			$cntrlrs = $this->getControllers();
			do {
				$id = substr( uniqid( rand() ), 0, 3 );
			} while( array_key_exists( $id, $cntrlrs ) );
			return $id;
		}

		/**
		 * Returns true if the layout file exists
		 *
		 * @return bool
		 */
		public function exists() {
			return file_exists( $this->path );
		}

		/**
		 * Sets the URL regex to be used for this layout
		 *
		 * @param string $regex
		 * @return object
		 */
		public function setRegex( $regex ) {
			$this->regex = $regex;
			return $this;
		}

		/**
		 * Gets the URL regex used for this layout
		 *
		 * @return string
		 */
		public function getRegex() {
			return $this->regex;
		}

		/**
		 * Sets the name of the layout
		 *
		 * @param string $name
		 * @return bool
		 */
		public function setName( $name ) {
			$this->name = $name;
			return $this;
		}

		/**
		 * Gets the name of the layout
		 *
		 * @return string
		 */
		public function getName() {
			return $this->name;
		}

		/**
		 * Gets all controllers in the layout file. If a sector is specified
		 * then get only those attached to that sector.
		 *
		 * @param string $inSector
		 * @return array
		 */
		public function getControllers( $inSector=null ) {
			if ( empty( $this->controllers ) ) {
				$cacheKey = 'layout_cntrlrs_'.$this->name;
				if ( ($this->controllers = $this->_cache->get($cacheKey)) == false ) {
					$this->controllers = array();
					foreach( $this->dom->getElementsByTagName('controller') as $node ) {
						// Gather all configuration values
						$config = array('displayTitle' 	=> true,
										'customTitle' 	=> null,
										'htmlWrapClass'	=> null);
						foreach( $node->getElementsByTagName('config')->item(0)->childNodes as $confNode ) {
							$config[ $confNode->nodeName ] = $confNode->nodeValue;
						}
						// 2.3.51 changed attr 'for_sector' into 'sector', use older if exists
						if ( ($sector = $node->getAttribute('for_sector')) == false ) {
							$sector = $node->getAttribute( 'sector' );
						}
						// Store the controllers
						$cid = $node->getAttribute( 'id' );
						$this->controllers[ $cid ] = array(
															'id'	=> $cid,
															'order'	=> (int) $node->getAttribute( 'order' ),
															'sector'=> strtoupper( $sector ),
															'mod'	=> $node->getElementsByTagName( 'mod' )->item(0)->nodeValue,
															'con'	=> $node->getElementsByTagName( 'con' )->item(0)->nodeValue,
															'sec'	=> $node->getElementsByTagName( 'sec' )->item(0)->nodeValue,
															'config'=> $config,
															);
					}
					// Normalize, order and cache the controllers
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
				foreach( $this->controllers as $cntrlr ) {
					if ( $cntrlr['sector'] == $inSector ) {
						$controllers[ $cntrlr['id'] ] = $cntrlr;
					}
				}
				return $controllers;
			}
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
			throw new Layout_ControllerNoExist( 'layout cntrlr "'.$id.'" does not exist' );
		}

		/**
		 * Adds a new controller to the layout XML. The unique ID of the attached
		 * cntrlr will be returned.
		 *
		 * @param string $sector
		 * @param array $details
		 * @param int $id
		 * @return int
		 */
		public function addController( $sector, array $details, $id=null ) {
			$details = array(
							'id'		=> $id ? $id : $this->makeCntrlrUid(),
							'sector'	=> $sector,
							'order'		=> isset($details['order']) ? $details['order'] : 0,
							'mod'		=> isset($details['mod']) ? $details['mod'] : 'index',
							'con'		=> isset($details['con']) ? $details['con'] : 'index',
							'sec'		=> isset($details['sec']) ? $details['sec'] : 'index',
							'config'	=> array_merge( array('displayTitle' => true, 'customTitle' => ''),
														(isset($details['config']) ? $details['config'] : array())
													),
							);
			// Create the new element with attributes
			$cntrlrElement = $this->dom->createElement( 'controller' );
			foreach( array('id', 'sector', 'order') as $attr ) {
				$attrElement = $this->dom->createAttribute( $attr );
				$attrElement->appendChild( $this->dom->createTextNode( $details[ $attr ] ) );
				$cntrlrElement->appendChild( $attrElement );
			}
			// Add all of the elements for this controller.
			foreach( array('mod', 'con', 'sec', 'config') as $key ) {
				$val = $details[ $key ];
				$element = $this->dom->createElement( $key );
				if ( is_array( $val ) ) {
					// Configuration values, currently only 1 level
					foreach( $val as $confKey=>$confVal ) {
						$confElement = $this->dom->createElement( $confKey );
						$method = zula_needs_cdata( $confVal ) ? 'createCDATASection' : 'createTextNode';
						$confElement->appendChild( $this->dom->$method( (string) $confVal ) );
						$element->appendChild( $confElement );
					}
				} else {
					$method = zula_needs_cdata( $val ) ? 'createCDATASection' : 'createTextNode';
					$element->appendChild( $this->dom->$method( $val ) );
				}
				$cntrlrElement->appendChild( $element );
			}
			$this->dom->documentElement->appendChild( $cntrlrElement );
			/**
			 * Add ACL resource with default permissions (if needed), cleanup and return
			 */
			$resource = 'layout_controller_'.$details['id'];
			if ( !$this->_acl->resourceExists( $resource ) ) {
				$this->_acl->allow( $resource, 'group_guest' );
				$this->_acl->allow( $resource, 'group_root' );
			}
			$this->_cache->delete( 'layout_cntrlrs_'.$this->name );
			Hooks::notifyAll( 'layout_add_cntrlr', $details );
			return $details['id'];
		}

		/**
		 * Removes a cntrlr by its unique id
		 *
		 * @param int $id
		 * @return bool
		 */
		public function detachController( $id ) {
			if ( !$this->controllerExists( $id ) ) {
				throw new Layout_ControllerNoExist;
			}
			$xPath = new DomXpath( $this->dom );
			$cntrlr = $xPath->query( '/controllers/controller[@id='.$id.']' )->item(0);
			if ( $cntrlr === null ) {
				return false;
			} else {
				$this->dom->documentElement->removeChild( $cntrlr );
				$this->_cache->delete( 'layout_cntrlrs_'.$this->name );
				Hooks::notifyAll( 'layout_detach_cntrlr', $id );
				return true;
			}
		}

		/**
		 * Edit details of an existing controller. The 'mod' value can never change
		 * by this method.
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
			$path = trim($path) ? $path : $this->path;
			if (
				(file_exists( $path ) && zula_is_writable( $path ) || !file_exists( $path ) && zula_is_writable( dirname($path) ))
				&& $this->dom->save( $path )
			) {
				if ( Registry::has( 'sql' ) ) {
					if ( ($regex = $this->getRegex()) ) {
						if ( $this->_sql->getAttribute( PDO::ATTR_DRIVER_NAME ) == 'sqlsrv' ) {
							$stmt = 'MERGE INTO {PREFIX}layouts AS dest
									USING (VALUES(:name, :regex)) AS src(name, regex)
										ON dest.name = src.name
									WHEN MATCHED THEN
										UPDATE SET regex = src.regex
									WHEN NOT MATCHED THEN
										INSERT (name, regex) VALUES(src.name, src.regex);';
						} else {
							$stmt = 'INSERT INTO {PREFIX}layouts (name, regex) VALUES (?, ?)
									ON DUPLICATE KEY UPDATE regex = VALUES(regex)';
						}
						$pdoSt = $this->_sql->prepare( $stmt );
						$pdoSt->execute( array($this->name, $this->getRegex()) );
					} else {
						$pdoSt = $this->_sql->prepare( 'DELETE FROM {PREFIX}layouts WHERE name = ?' );
						$pdoSt->execute( array($this->name) );
					}
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
			if ( zula_is_deletable( $this->path ) && unlink( $this->path ) ) {
				$pdoSt = $this->_sql->prepare( 'DELETE FROM {PREFIX}layouts WHERE name = ?' );
				$pdoSt->execute( array($this->name) );
				return true;
			} else {
				return false;
			}
		}

	}

?>
