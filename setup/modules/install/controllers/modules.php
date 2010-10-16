<?php

/**
 * Zula Framework Module
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Setup
 */

	class Install_controller_modules extends Zula_ControllerBase {

		/**
		 * Installs all available modules
		 *
		 * @return bool
		 */
		public function indexSection() {
			$this->setTitle( t('Module instalation') );
			/**
			 * Make sure user is not trying to skip ahead
			 */
			if ( !isset( $_SESSION['installStage'] ) || $_SESSION['installStage'] !== 5 ) {
				return zula_redirect( $this->_router->makeUrl('install', 'security') );
			}
			Module::setDirectory( _REAL_MODULE_DIR );
			foreach( Module::getModules( Module::_INSTALLABLE ) as $modname ) {
				$module = new Module( $modname );
				$module->install();
			}
			$this->setProjectDefaults();
			Module::setDirectory( $this->_zula->getDir( 'modules' ) );
			$this->_event->success( t('Successfully installed available modules') );
			++$_SESSION['installStage'];
			return zula_redirect( $this->_router->makeUrl('install', 'settings') );
		}

		/**
		 * Sets project defaults such as ACL rules, additional content,
		 * load order of modules etc.
		 *
		 * @return null
		 */
		protected function setProjectDefaults() {
			$guestInherit = $adminInherit = array('group_root');
			foreach( $this->_acl->getRoleTree( 'group_guest', true ) as $role ) {
				array_unshift( $guestInherit, $role['name'] );
			}
			foreach( $this->_acl->getRoleTree( 'group_admin', true ) as $role ) {
				array_unshift( $adminInherit, $role['name'] );
			}
			$aclResources = array(
								# main-default content layout
								'layout_controller_456'	=> $guestInherit,
								'layout_controller_974'	=> $guestInherit,
								'layout_controller_110'	=> $guestInherit,
								'layout_controller_119'	=> $guestInherit,

								# fpsc-main
								'layout_controller_168'	=> $guestInherit,

								# admin-default content layout
								'layout_controller_409'	=> $adminInherit,
								'layout_controller_123'	=> $adminInherit,
								'layout_controller_909'	=> $adminInherit,

								# fpsc-admin
								'layout_controller_551'	=> $adminInherit,
								);
			foreach( $aclResources as $resource=>$roles ) {
				$this->_acl->allowOnly( $resource, $roles );
			}
			// Setup module load order
			if ( Module::exists( 'comments' ) ) {
				$comments = new Module( 'comments' );
				$comments->setLoadOrder( 1 ); # Should force it below Shareable by default
			}
			if ( Module::exists( 'contact' ) ) {
				// Set the contact form email to be the same as the initial user
				try {
					$this->_sql->exec( 'UPDATE {SQL_PREFIX}mod_contact
										SET email = (SELECT email FROM {SQL_PREFIX}users WHERE id = 2)' );
				} catch ( Exception $e ) {
				}
			}
		}

	}

?>
