<?php

/**
 * Zula Framework Module
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Module_manager
 */

	class Module_manager_controller_install extends Zula_ControllerBase {

		/**
		 * Constructor function
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
		 * Magic method allowing for shorter URLs. This attempts
		 * to install the provided module name.
		 *
		 * @param string $name
		 * @param array $args
		 * @return string
		 */
		public function __call( $name, array $args ) {
			$this->setTitle( t('Module installation') );
			$this->setOutputType( self::_OT_CONFIG );
			// Get correct module name to install
			try {
				$name = substr( $name, 0, -7 );
				$module = new Module( $name );
				$result = $module->install();
				if ( $result === true ) {
					$this->_event->success( sprintf( 'Module "%s" installed successfully, please check permissions', $module->name ) );
					return zula_redirect( $this->_router->makeUrl('module_manager', 'permission', $module->name) );
				} else if ( $result === false ) {
					$this->_event->error( sprintf( 'Module "%s" is not installable', $module->name ) );
				} else {
					// Some dependency checks failed
					$this->setTitle( t('Module dependencies not satisfied') );
					$view = $this->loadView( 'install/failed.html' );
					$result['module_name'] = $module->name;
					$view->assign( $result );
					return $view->getOutput();
				}
			} catch ( Router_ArgNoExist $e ) {
				$this->_event->error( t('Please provide a module name to install') );
			} catch ( Module_NoExist $e ) {
				$this->_event->error( sprintf( t('Module "%1$s" does not exist'), $name ) );
			}
			return zula_redirect( $this->_router->makeUrl( 'module_manager', 'install' ) );
		}

		/**
		 * Lists all modules that are avaliable for installation
		 *
		 * @return string
		 */
		public function indexSection() {
			$this->setTitle( t('Install module') );
			$this->setOutputType( self::_OT_CONFIG );
			$view = $this->loadView( 'install/list.html' );
			$view->assign( array(
								'modules'	=> Module::getModules( Module::_INSTALLABLE ),
								));
			return $view->getOutput();
		}

	}

?>
