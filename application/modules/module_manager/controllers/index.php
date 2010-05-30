<?php

/**
 * Zula Framework Module (Module Manager)
 * --- Acts as a 'Portal' to the different modules and also allows the user
 * to manage permissions
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Module_manager
 */

	class Module_manager_controller_index extends Zula_ControllerBase {

		/**
		 * Constructor function
		 *
		 * Sets all the different page links
		 */
		public function __construct( $moduleDetails, $config, $sector ) {
			parent::__construct( $moduleDetails, $config, $sector );
			$this->setPageLinks( array(
										t('View modules')    	=> $this->_router->makeUrl( 'module_manager' ),
										t('Manage modules')    	=> $this->_router->makeUrl( 'module_manager', 'config' ),
										t('Install modules')  	=> $this->_router->makeUrl( 'module_manager', 'install' ),
										t('Load order')			=> $this->_router->makeUrl( 'module_manager', 'config', 'loadorder' ),
										));
		}

		/**
		 * Displays all of the other modules that he user has permission to
		 * as well as putting them into the correct Category. From there you can
		 * either configure the module or configure the permissions that the
		 * module provides.
		 *
		 * @return string
		 */
		public function indexSection() {
			$this->setTitle( t('Module Manager') );
			$this->setOutputType( self::_OT_CONTENT_INDEX );
			// Gather all modules
			$categories = array();
			foreach( Module::getModules() as $module ) {
				$aclRule = $module.'_global';
				if ( $this->_acl->resourceExists( $aclRule ) && $this->_acl->check( $aclRule ) ) {
					$tmpModule = new Module( $module );
					$details = $tmpModule->getDetails();
					// Check which controller the icon/button should link to.
					if ( $tmpModule->controllerExists( 'config' ) ) {
						$details['cntrlr'] = 'config';
					} else if ( $tmpModule->controllerExists( 'index' ) ) {
						$details['cntrlr'] = 'index';
					} else {
						continue;
					}
					// Build correct category name
					$category = trim( $tmpModule->category ) ? zula_strtolower( $tmpModule->category) : t('Unknown');
					$categories[ $category ][] = $details;
				}
			}
			foreach( $categories as $cat=>$mod ) {
				usort( $categories[ $cat ], array($this, 'sort') );
			}
			ksort( $categories );
			// Output main view
			$this->_theme->addJsFile( 'general.js' );
			$this->addAsset( 'js/filter.js' );
			$view = $this->loadView( 'index/main.html' );
			$view->assign( array(
								'categories' => $categories,
								));
			return $view->getOutput();
		}

		/**
		 * Sorts the modules alphabetically
		 *
		 * @param object $a
		 * @param object $b
		 * @return int
		 */
		protected function sort( $a, $b ) {
			return strcmp( $a['title'], $b['title'] );
		}

	}

?>
