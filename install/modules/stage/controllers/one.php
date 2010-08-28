<?php

/**
 * Zula Framework
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Evangelos Foutras
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Installer
 */

	class Stage_controller_one extends Zula_ControllerBase {

		/**
		 * Security Check, to check that the user installing TangoCMS
		 * is the owner of the server/hosting account
		 *
		 * @return strng
		 */
		public function indexSection() {
			$this->setTitle( t('Security check') );
			// Set default install stage and attempt to check for code file
			$_SESSION['install_stage'] = 1;
			if ( !empty( $_SESSION['security_code'] ) ) {
				// Check if security file exists
				$file = $this->_zula->getDir( 'install' ).'/'.$_SESSION['security_code'].'.txt';
				if ( file_exists( $file ) ) {
					unset( $_SESSION['security_code'] );
					$_SESSION['install_stage']++;
					return zula_redirect( $this->_router->makeUrl( 'stage', 'two' ) );
				} else {
					$this->_event->error( sprintf( t('Verification file "%s" does not exist'), $file ) );
				}
			}
			// Build the view
			if ( !isset( $_SESSION['security_code'] ) ) {
				$_SESSION['security_code'] = uniqid( 'zula_verify_' );
			}
			$view = $this->loadView( 'stage1/main.html' );
			$view->assign( array(
								'CODE' => $_SESSION['security_code'],
								));
			return $view->getOutput();
		}

	}

?>
