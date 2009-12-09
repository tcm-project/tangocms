<?php
// $Id: permission.php 2798 2009-11-24 12:15:41Z alexc $

/**
 * Zula Framework Module (Module Manager)
 * --- Lists all of the ACL Resources for the specified module. Basically any
 * Resource that has the modules name as it's prefix.
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Module_manager
 */

	class Module_manager_controller_permission extends Zula_ControllerBase {

		/**
		 * Constructor
		 *
		 * @return object
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
		 * Allow for shorter URLs
		 *
		 * @param string $name
		 * @param array $args
		 * @return mixed
		 */
		public function __call( $name, $args ) {
			return $this->indexSection( substr( $name, 0, -7 ) );
		}

		/**
		 * Displays a form produced by the ACL Library that allows the
		 * user the modify the ACL Rules for Resources assosicated with
		 * the specified module.
		 *
		 * @return string
		 */
		public function indexSection( $name=null ) {
			$this->setTitle( t('Manage Permissions') );
			$this->_locale->textDomain( $this->textDomain() );
			$this->setOutputType( self::_OT_CONFIG );
			if ( !trim( $name ) ) {
				$this->_event->error( t('No module provided, could not get permissions') );
				return zula_redirect( $this->_router->makeUrl( 'module_manager' ) );
			} else if ( !$this->_acl->check( 'module_manager_view_permissions' ) ) {
				throw new Module_NoPermission;
			}
			/**
			 * Check if we have a module to produce the ACL form for and
			 * then check if the user actually has permission to that module
			 */
			try {
				$module = new Module( $name );
				$moduleDetails = $module->getDetails();
				// Check if user has global permission
				if ( !$this->_acl->check( $module->name.'_global' ) ) {
					$this->_event->error( sprintf( t('Sorry, you do not have global permission to module "%1$s"'), $module->name ) );
					return zula_redirect( $this->_router->makeUrl( 'module_manager' ) );
				}
				$this->setTitle( sprintf( t('"%1$s" Permissions'), $module->title ), false );
			} catch ( Module_NoExist $e ) {
				$this->_event->error( sprintf( t('Module "%1$s" does not exist, could not get details'), $name ) );
				return zula_redirect( $this->_router->makeUrl( 'module_manager' ) );
			}
			/**
			 * Return the form built by the ACL library, setting the correct
			 * URL it should be posted to and which Resources to show
			 */
			$resources = array();
			foreach( $this->_acl->getAllResources( $module->name ) as $resource ) {
				if ( substr( $resource['name'], 0, strlen($module->name)+1 ) == $module->name.'_' ) {
					$resources[] = $resource['name'];
				}
			}
			// Order the resources and remove the _global ACL rule, making sure that it's always at the beginning
			sort( $resources );
			$globalIndex = array_search( $module->name.'_global', $resources );
			if ( $globalIndex !== false ) {
				array_unshift( $resources, $resources[ $globalIndex ] );
				unset( $resources[ $globalIndex+1 ] );
			}
			// Begin to build the main form
			$formUrl = $this->_router->makeUrl( 'module_manager', 'permission', 'update' );
			$view = $this->loadView( 'permission/form.html' );
			$view->assign( array(
								'MODULE'	=> $moduleDetails,
								));

			// Set the correct text domain to use to the module when building the form
			$this->_locale->bindTextDomain( $module->name, $this->_zula->getDir( 'modules' ).'/'.$module->name.'/locale' );
			$this->_locale->textDomain( $module->name );
			$aclForm = $this->_acl->buildForm( $resources, 'group_' );
			$this->_locale->textDomain( Locale::_DTD ); # Reset text domain

			$view->assignHtml( array(
									'ACL_FORM'	=> $aclForm,
									'CSRF'		=> $this->_input->createToken( true ),
									));
			return $view->getOutput();
		}

		/**
		 * Updates the ACL Rules for the provided ACL Resources and Roles
		 * from a specified module
		 *
		 * @return bool
		 */
		public function updateSection() {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('Update Module Permissions') );
			$this->setOutputType( self::_OT_CONFIG );
			if ( !$this->_acl->check( 'module_manager_edit_permissions' ) ) {
				throw new Module_NoPermission;
			} else if ( !$this->_input->checkToken() ) {
				$this->_event->error( Input::csrfMsg() );
			} else {
				/**
				 * Attempt to get details for the module provided, and then also
				 * check if the user has global permission to the module he/she
				 * is updating permission rules for
				 */
				try {
					$name = $this->_input->post( 'module' );
					$module = new Module( $name );
					$moduleDetails = $module->getDetails();
					// Check if user has global permission
					if ( !$this->_acl->check( $module->name.'_global' ) ) {
						$this->_event->error( sprintf( t('Sorry, you do not have global permission to module "%1$s"'), $module->name ) );
						return zula_redirect( $this->_router->makeUrl( 'module_manager' ) );
					}
				} catch ( Input_KeyNoExist $e ) {
					$this->_event->error( t('No module provided, could not get permissions') );
					return zula_redirect( $this->_router->makeUrl( 'module_manager' ) );
				} catch ( Module_NoExist $e ) {
					$this->_event->error( sprintf( t('Module "%1$s" does not exist, could not get details'), $name  ) );
					return zula_redirect( $this->_router->makeUrl( 'module_manager' ) );
				}
				// Gather all of the ACL Resources for this module, check we have all from the POST data
				foreach( $this->_acl->getAllResources( $module->name  ) as $resource ) {
					try {
						$roles = $this->_input->post( 'acl_resources/'.$resource['name'] );
						$this->_acl->allowOnly( $resource['name'], $roles );
					} catch ( Input_KeyNoExist $e ) {
						$roles = array( 'group_root' => 1 );
					} catch ( Acl_InvalidName $e ) {
						$this->_event->error( sprintf( t('Invalid resource name of "%1$s". Could not update ACL Rules'), $resource['name'] ) );
					}
				}
				$this->_event->success( sprintf( t('Updated permissions for module "%1$s"'), $module->title  ) );
			}
			return zula_redirect( $this->_router->makeUrl( 'module_manager' ) );
		}

	}

?>
