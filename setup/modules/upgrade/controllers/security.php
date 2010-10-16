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

	class Upgrade_controller_security extends Zula_ControllerBase {

		/**
		 * Constructor
		 *
		 * @return object
		 */
		public function __construct( $moduleDetails, $config, $sector ) {
			parent::__construct( $moduleDetails, $config, $sector );
			$this->_config->update( 'config/title',  sprintf('%s %s upgrader', _PROJECT_NAME, _PROJECT_LATEST_VERSION) );
		}

		/**
		 * Security check to ensure only those authorized can perform
		 * the upgrade. This is skiped when using CLI
		 *
		 * @return string
		 */
		public function indexSection() {
			$this->setTitle( t('Security check') );
			if ( $this->_zula->getMode() == 'cli' ) {
				return zula_redirect( $this->_router->makeUrl( 'upgrade', 'checks' ) );
			} else if ( !isset( $_SESSION['upgradeStage'] ) || $_SESSION['upgradeStage'] !== 2 ) {
				return zula_redirect( $this->_router->makeUrl( 'upgrade', 'version' ) );
			}
			if ( !empty( $_SESSION['securityCode'] ) ) {
				// We've got a security code, see if the file exists
				$file = $this->_zula->getDir( 'setup' ).'/'.$_SESSION['securityCode'].'.txt';
				if ( file_exists( $file ) ) {
					unset( $_SESSION['securityCode'] );
					++$_SESSION['upgradeStage'];
					return zula_redirect( $this->_router->makeUrl( 'upgrade', 'checks' ) );
				} else {
					$this->_event->error( sprintf( t('Verification file "%s" does not exist'), $file ));
				}
			}
			if ( !isset( $_SESSION['securityCode'] ) ) {
				$_SESSION['securityCode'] =  uniqid( 'zula_verify_' );
			}
			$view = $this->loadView( 'security.html' );
			$view->assign( array('code' => $_SESSION['securityCode']) );
			return $view->getOutput();
		}

	}

?>
