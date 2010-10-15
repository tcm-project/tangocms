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
 * @package Zula_Installer
 */

	class Index_controller_index extends Zula_ControllerBase {

		/**
		 * Displays a simple welcome message with links to either
		 * the installer or upgrader
		 *
		 * @return string
		 */
		public function indexSection() {
			$this->setTitle( t('Welcome!') );
			$view = $this->loadView( 'welcome.html' );
			return $view->getOutput();
		}

	}

?>
