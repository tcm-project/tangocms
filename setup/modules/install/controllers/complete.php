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

	class Install_controller_complete extends Zula_ControllerBase {

		/**
		 * Displays a simple message saying the installation is complete
		 *
		 * @return string
		 */
		public function indexSection() {
			$this->setTitle( t('Install complete!') );
			/**
			 * Check user is not skipping ahead
			 */
			if ( !isset( $_SESSION['installStage'] ) || $_SESSION['installStage'] !== 6 ) {
				return zula_redirect( $this->_router->makeUrl('install', 'security') );
			}
			$configIni = Registry::get( 'config_ini' );
			try {
				$configIni->update( 'acl/enable', 'true' );
				$configIni->writeIni();
			} catch ( Config_ini_FileNotWriteable $e ) {
				$this->_event->error( $e->getMessage() );
			}
			$view = $this->loadView( 'complete.html' );
			return $view->getOutput();
		}

	}

?>
