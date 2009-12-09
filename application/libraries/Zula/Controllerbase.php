<?php

/**
 * Zula Framework Controller Base
 * --- Provides a common base of methods that all controllers must extend
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula
 */

	abstract class Zula_ControllerBase extends Zula_Base {

		/**
		 * Different output types a controller can output
		 */
		const
				_OT_GENERAL			= 1, # Default
				_OT_CONTENT_STATIC	= 2,
				_OT_CONTENT_DYNAMIC	= 4,
				_OT_CONTENT_INDEX	= 8,
				_OT_CONTENT			= 14,
				_OT_COLLECTIVE		= 16,
				_OT_CONFIG			= 32,
				_OT_INFORMATIVE		= 64;

		/**
		 * Details about the parent module this controller belongs to
		 * @var array
		 */
		protected $moduleDetails = array();

		/**
		 * Sector that the module is loaded into (if any)
		 * @var string
		 */
		protected $sector = null;

		/**
		 * Old configuration values used, and will be restored
		 * once the controller has finished loading.
		 * @var array
		 */
		protected $oldConfig = array();

		/**
		 * Every model that has been loaded for this controller
		 * @var array
		 */
		protected $models = array();

		/**
		 * The text domain that this controller will use
		 * @var string
		 */
		protected $textDomain = '';

		/**
		 * Output type for the controller
		 * @param int
		 */
		protected $outputType = 1; # 1 Defaults to self::_OT_GENERAL

		/**
		 * Every stored page link
		 * @var array
		 */
		protected $pageLinks = array();

		/**
		 * Constructor
		 * Sets the module details, and any temp configuration values
		 * that may have been set for this module.
		 *
		 * @param array $moduleDetails
		 * @param array $config
		 * @param string $sector
		 * @return object
		 */
		public function __construct( array $moduleDetails, array $config=array(), $sector=null ) {
			$this->moduleDetails = $moduleDetails;
			$this->sector = strtoupper( $sector );
			// Store the older config, to be restored later
			if ( $this->_config->has( $moduleDetails['name'] ) ) {
				$this->oldConfig = $this->_config->get( $moduleDetails['name'] );
			}
			foreach( $config as $key=>$val ) {
				$key = $moduleDetails['name'].'/'.$key;
				if ( $this->_config->has( $key ) ) {
					$this->_config->update( $key, $val );
				} else {
					$this->_config->add( $key, $val );
				}
			}
			if ( _APP_MODE == 'installation' ) {
				$domain = 'zula-installer';
				$path = $this->_zula->getDir( 'locale' );
			} else {
				$domain = _PROJECT_ID.'-'.$moduleDetails['name'];
				$path = $this->getPath().'/locale';
			}
			$this->_locale->bindTextDomain( $domain, $path );
			$this->textDomain = $this->_locale->textDomain( $domain );
			$this->_log->message( 'Cntrlr constructed as "'.$moduleDetails['name'].'/'.$sector.'"', Log::L_DEBUG );
		}

		/**
		 * Destructor
		 * Restores the configuration values that were previously altered
		 *
		 * @return void
		 */
		public function __destruct() {
			foreach( $this->oldConfig as $key=>$val ) {
				$key = $this->getDetail( 'name' ).'/'.$key;
				if ( $this->_config->has( $key ) ) {
					$this->_config->update( $key, $val );
				}
			}
		}

		/**
		 * Loads a model for the current controllers module, or
		 * for a specified module.
		 *
		 * @param string $model
		 * @param string $module
		 * @return object
		 */
		protected function _model( $model=null, $module='' ) {
			if ( $module !== null && !trim( $module ) ) {
				$module = $this->getDetail( 'name' );
			}
			if ( $model === null ) {
				$model = $this->getDetail( 'name' );
			}
			return parent::_model( $model, $module );
		}

		/**
		 * Returns the text domain that should be used for this controller
		 * or module
		 *
		 * @return string
		 */
		protected function textDomain() {
			return $this->textDomain;
		}

		/**
		 * Sets the output type for the controller
		 *
		 * @param int $type
		 * @return bool
		 */
		public function setOutputType( $type ) {
			$this->outputType = abs( $type );
		}

		/**
		 * Gets the output type
		 *
		 * @return int
		 */
		public function getOutputType() {
			return $this->outputType;
		}

		/**
		 * Will return a detail, if it exists, about the current controller
		 *
		 * @param string $detail
		 * @return string
		 */
		public function getDetail( $detail ) {
			if ( $this->detailExists( $detail ) ) {
				return $this->moduleDetails[ $detail ];
			} else {
				trigger_error( 'Zula_ControllerBase::getDetail() detail "'.$detail.'" does not exist', E_USER_WARNING );
				return false;
			}
		}

		/**
		 * Checks if a detail of the current loaded controller exists
		 *
		 * @param string $detail
		 * @return bool
		 */
		public function detailExists( $detail ) {
			return isset( $this->moduleDetails[ $detail ] );
		}

		/**
		 * Will return evey detail about the current loaded controller
		 *
		 * @return array
		 */
		public function getAllDetails() {
			return $this->moduleDetails;
		}

		/**
		 * Adds more entries to the page links array
		 *
		 * @param array $links
		 * @return bool
		 */
		public function setPageLinks( $links ) {
			if ( !is_array( $links ) ) {
				trigger_error( 'Zula_ControllerBase::setPageLink() could not set page links. Value given is not an array', E_USER_WARNING );
				return false;
			} else if ( empty( $this->pageLinks ) ) {
				$this->pageLinks = $links;
			} else {
				$this->pageLinks = array_merge( $this->pageLinks, $links );
				return true;
			}
		}

		/**
		 * Returns all of the page links currently set
		 *
		 * @return array
		 */
		public function getPageLinks() {
			return $this->pageLinks;
		}

		/**
		 * Removes/Purges all currently set page links
		 *
		 * @return bool
		 */
		public function purgePageLinks() {
			$this->pageLinks = array();
			return true;
		}

		/**
		 * Makes it easier to load view files by creating a new View object
		 * with the specified view file to be loaded automagically.
		 *
		 * Also adjusts the path used according to if the main Controller is
		 * using modules or not.
		 *
		 * @param string $viewFile
		 * @return object
		 */
		protected function loadView( $viewFile ) {
			$tmpView = new View( $viewFile, $this->getDetail( 'name' ) );
			return $tmpView;
		}

		/**
		 * Changes the title that will be displayed for the controller
		 * (if anywhere is set to show it). Default will be the controllers name
		 * The string given is also sent for translation.
		 *
		 * @param string $title
		 * @return bool
		 */
		public function setTitle( $title ) {
			$this->moduleDetails['title'] = (string) $title;
			return true;
		}

		/**
		 * Checks if the controller is running in the specified sector
		 *
		 * @param string $sector
		 * @return bool
		 */
		public function inSector( $sector ) {
			return strtoupper( $sector ) == $this->getSector();
		}

		/**
		 * Gets the sector that the controller is running in
		 *
		 * @return string
		 */
		public function getSector() {
			return $this->sector;
		}

		/**
		 * Gets the path the current controller is in
		 *
		 * @return string
		 */
		public function getPath() {
			return $this->_zula->getDir( 'modules' ).'/'.$this->getDetail( 'name' );
		}

		/**
		 * Adds a new virtual asset to the theme, such as a JS or CSS file
		 *
		 * @param string|array $asset
		 * @return int
		 */
		protected function addAsset( $assets ) {
			if ( !is_array( $assets ) ) {
				$assets = array($assets);
			}
			$assetsAdded = 0;
			foreach( $assets as $asset ) {
				$asset = trim( $asset, '/ ' );
				$extension = strtolower( pathinfo($asset, PATHINFO_EXTENSION) );
				if ( $extension == 'js' ) {
					$this->_theme->addJsFile( $asset, true, $this->getDetail('name') );
				} else if ( $extension == 'css' ) {
					$url = $this->_router->makeUrl( 'assets/v/'.$this->getDetail('name').'/'.$asset );
					if ( $this->_theme->addHead( 'css', array('href' => $url) ) ) {
						++$assetsAdded;
					}
				}
			}
			return $assetsAdded;
		}

	}

?>
