<?php
// $Id: config.php 2798 2009-11-24 12:15:41Z alexc $

/**
 * Zula Framework Module (Module Manager)
 * --- Allows a user to Enable or Disable modules
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Module_manager
 */

	class Module_manager_controller_config extends Zula_ControllerBase {

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
		 * Enables or Disables selected modules, cheat way to combine the
		 * two very similar methods, saves code.
		 *
		 * @param string $name
		 * @param array $args
		 * @return mixed
		 */
		public function __call( $name, $args ) {
			switch( substr($name, 0, -7) ) {
				case 'enmod':
					$op = 'enable';
					break;
				case 'dismod':
					$op = 'disable';
					break;
				default:
					throw new Module_ControllerNoExist;
			}
			$this->setOutputType( self::_OT_CONFIG );
			if ( !$this->_acl->check( 'module_manager_'.$op.'_modules' ) ) {
				throw new Module_NoPermission;
			} else if ( !$this->_input->checkToken() ) {
				$this->_event->error( Input::csrfMsg() );
			} else {
				// Get all modules that need to be enabled/disabled
				try {
					$modules = $this->_input->post( 'modules' );
					$count = 0;
					foreach( $modules as $module ) {
						try {
							$tmpModule = new Module( $module );
							// Check if user has global permission to do so
							$aclResource = $module.'_global';
							if ( !$this->_acl->resourceExists( $aclResource ) || !$this->_acl->check( $aclResource ) ) {
								throw new Module_NoPermission;
							}
							if ( $op == 'enable' ) {
								$tmpModule->enable();
								++$count;
							} else if ( in_array( $tmpModule->name, array($this->getDetail('name'), 'session') ) ) {
								// User is trying to disabled this module, that can't really happen
								$this->_event->error( sprintf( t('Sorry, you can not disable the module "%1$s"'), $tmpModule->name ) );
							} else {
								$tmpModule->disable();
								++$count;
							}
						} catch ( Module_NoExist $e ) {
						}
					}
					if ( $count > 0 ) {
						if ( $op == 'enable' ) {
							$msg = count($modules) > 1 ? t('Enabled selected modules') : sprintf( t('Enabled module "%1$s"'), $tmpModule->name );
						} else {
							$msg = count($modules) > 1 ? t('Disabled selected modules') : sprintf( t('Disabled module "%1$s"'), $tmpModule->name );
						}
						$this->_event->success( $msg );
					}
				} catch ( Input_KeyNoExist $e ) {
					$this->_event->error( t('No modules selected') );
				}
			}
			return zula_redirect( $this->_router->makeUrl( 'module_manager', 'config' ) );
		}
		
		/**
		 * Displays all of the modules so that a user can disable or
		 * enable any of them.
		 *
		 * @return string
		 */
		public function indexSection() {
			$this->setTitle( t('Enable/Disable Modules') );
			$this->setOutputType( self::_OT_CONFIG );
			$view = $this->loadView( 'config/main.html' );
			$view->assign( array(
								'DISABLED'	=> Module::getDisabledModules(),
								'ENABLED'	=> Module::getModules(),
								));
			$view->assignHtml( array(
									'CSRF' => array(
													'ENABLE'	=> $this->_input->createToken( true ),
													'DISABLE'	=> $this->_input->createToken( true ),
													),
									));
			return $view->getOutput();
		}

		/**
		 * Manages the Module (Hook) Load Order
		 *
		 * @return string|bool
		 */
		public function loadorderSection() {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('Module Load Order') );
			$this->setOutputType( self::_OT_CONFIG );
			// Build form validation
			$form = new View_form( 'config/loadorder.html', 'module_manager' );
			$form->action( $this->_router->makeUrl( 'module_manager', 'config', 'loadorder' ) );
			$form->addElement( 'module_manager/modules', null, t('Modules'), new Validator_Between(0,1000) );
			if ( $form->hasInput() && $form->isValid() ) {
				foreach( $form->getValues( 'module_manager/modules' ) as $tmpModule=>$order) {
					try {
						$module = new Module( $tmpModule );
						$module->setLoadOrder( $order );
					} catch ( Module_NoExist $e ) {
					}
				}
				$this->_event->success( t('Updated module load order') );
				return zula_redirect( $this->_router->makeUrl( 'module_manager', 'config', 'loadorder' ) );
			}
			$this->_theme->addJsFile( 'jQuery/plugins/dnd.js' );
			$this->addAsset( 'js/dnd_order.js' );
			$form->assign( array('MODULES' => Hooks::getLoadedModules()) );
			return $form->getOutput();
		}

	}

?>
