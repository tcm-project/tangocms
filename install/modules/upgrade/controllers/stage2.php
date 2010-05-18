<?php

/**
 * Zula Framework Upgrade Controller
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

	class Upgrade_controller_stage2 extends Zula_ControllerBase {

		/**
		 * Constructor function
		 */
		public function __construct( $moduleDetails, $config, $sector ) {
			parent::__construct( $moduleDetails, $config, $sector );
			$this->_config->update( 'config/title', _PROJECT_NAME.' '._PROJECT_LATEST_VERSION.' '.t('Upgrader') );
		}

		/**
		 * Security check to make sure the upgrader is only run by the owner
		 *
		 * @return string
		 */
		public function indexSection() {
			$this->_i18n->textDomain( $this->textDomain() );
			$this->setTitle( t('Security Check') );
			if ( !isset( $_SESSION['upgrade_stage'] ) || $_SESSION['upgrade_stage'] !== 2 ) {
				return zula_redirect( $this->_router->makeUrl( 'upgrade', 'stage1' ) );
			}
			if ( !empty( $_SESSION['upgrade_security_code'] ) ) {
				// We've got a security code, see if the file exists
				$file = $this->_zula->getDir( 'install' ).'/'.$_SESSION['upgrade_security_code'].'.txt';
				if ( file_exists( $file ) ) {
					unset( $_SESSION['upgrade_security_code'] );
					$_SESSION['upgrade_stage']++;
					return zula_redirect( $this->_router->makeUrl( 'upgrade', 'stage3' ) );
				} else {
					$this->_event->error( sprintf( t('Verification file "%s" does not exist'), $file ));
				}
			}
			// Build the view
			if ( !isset( $_SESSION['upgrade_security_code'] ) ) {
				$_SESSION['upgrade_security_code'] =  uniqid( 'zula_verify_' );
			}
			$view = $this->loadView( 'stage2/security_check.html' );
			$view->assign( array(
								'CODE' => $_SESSION['upgrade_security_code'],
								));
			return $view->getOutput();
		}

	}

?>
