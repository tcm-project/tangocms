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

	class Stage_controller_six extends Zula_ControllerBase {

		/**
		 * Displays a simple message saying install complete
		 *
		 * @return string
		 */
		public function indexSection() {
			$this->_i18n->textDomain( $this->textDomain() );
			$this->setTitle( t('Install Complete!') );
			/**
			 * Check user is not skipping ahead
			 */
			if ( !isset( $_SESSION['install_stage'] ) || $_SESSION['install_stage'] !== 6 ) {
				return zula_redirect( $this->_router->makeUrl( 'stage', 'one' ) );
			}
			# Enable ACL
			$configIni = Registry::get( 'config_ini' );
			try {
				$configIni->update( 'acl/enable', 'true' );
				$configIni->writeIni();
			} catch ( Config_ini_FileNotWriteable $e ) {
				$this->_event->error( $e->getMessage() );
			}
			$view = $this->loadView( 'stage6/finish.html' );
			return $view->getOutput();
		}

	}

?>
