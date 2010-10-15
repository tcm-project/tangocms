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

	class Progress_controller_index extends Zula_ControllerBase {

		/**
		 * Displays a simple progress list to show the user how far
		 * they are within the install/upgrade
		 *
		 * @return string
		 */
		public function indexSection() {
			$reqCntrl = $this->_dispatcher->getDispatchData();
			if ( $reqCntrlr['module'] == 'stage' ) {
				$this->setTitle( t('Installation progress') );
				$view = $this->loadView( 'installation.html' );
				$stages = array(
								'one'	=> t('Security check'),
								'two'	=> t('Pre-installation checks'),
								'three'	=> t('SQL details'),
								'four'	=> t('First user'),
								'five'	=> t('Basic configuration'),
								'six'	=> t('Install complete!'),
								);
			} else if ( $reqCntrlr['module'] == 'upgrade' ) {
				$this->setTitle( t('Upgrade progress') );
				$view = $this->loadView( 'upgrade.html' );
				$stages = array(
								t('Version check'),
								t('Security check'),
								t('Pre-Upgrade checks'),
								t('Perform upgrades'),
								t('Upgrade complete!'),
								);
			} else {
				return false;
			}
			$view->assign( array(
								'stages' 	=> $stages,
								'cntrlr'	=> $reqCntrl['controller'],
								));
			return $view->getOutput();
		}

	}

?>
