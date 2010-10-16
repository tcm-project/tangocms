<?php

/**
 * Zula Framework Module
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Evangelos Foutras
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Setup
 */

	class Install_controller_security extends Zula_ControllerBase {

		/**
		 * Security check to ensure the user installing this application
		 * is authorized to run the installer. This is skiped when running
		 * via CLI.
		 *
		 * @return bool|strng
		 */
		public function indexSection() {
			$this->setTitle( t('Security check') );
			// Set default install stage and attempt to check for code file
			$_SESSION['install_stage'] = 1;
			if ( !empty( $_SESSION['security_code'] ) ) {
				$file = $this->_zula->getDir( 'setup' ).'/'.$_SESSION['security_code'].'.txt';
				if ( file_exists( $file ) ) {
					unset( $_SESSION['security_code'] );
					$_SESSION['install_stage']++;
					return zula_redirect( $this->_router->makeUrl('install', 'checks') );
				} else {
					$this->_event->error( sprintf( t('Verification file "%s" does not exist'), $file ) );
				}
			}
			if ( !isset( $_SESSION['security_code'] ) ) {
				$_SESSION['security_code'] = uniqid( 'zula_verify_' );
			}
			$view = $this->loadView( 'security.html' );
			$view->assign( array('code' => $_SESSION['security_code']) );
			return $view->getOutput();
		}

	}

?>
