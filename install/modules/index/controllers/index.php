<?php

/**
 * Index controller for the Installer/Upgrader of the Zula project
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

	class Index_controller_index extends Zula_ControllerBase {

		/**
		 * Displays a simple welcome message, giving the options
		 * of either upgrading ot doing a fresh install of TCM.
		 *
		 * @return string
		 */
		public function indexSection() {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( 'Welcome!' );
			// Display view
			$_SESSION['install_stage'] = 'one';
			$view = $this->loadView( 'welcome.html' );
			return $view->getOutput();
		}

	}

?>
