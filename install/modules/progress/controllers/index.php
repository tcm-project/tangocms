<?php

/**
 * Zula Framework Progress controller
 * --- Displays a very simple orderd list showing what stages are
 * left and have been done.
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

	class Progress_controller_index extends Zula_ControllerBase {

		/**
		 * Index section
		 * Basically a welcome message given options to either Upgrade or Fresh Install
		 */
		public function indexSection() {
			$this->_i18n->textDomain( $this->textDomain() );
			$reqCntrl = $this->_dispatcher->getDispatchData();
			switch( $reqCntrl['module'] ) {
				case 'stage':
					$this->setTitle( t('Installation Stages') );
					$view = $this->loadView( 'index/installation.html' );
					$stages = array(
									'one'	=> t('Security Check'),
									'two'	=> t('Pre-Installation Checks'),
									'three'	=> t('SQL Details'),
									'four'	=> t('First User'),
									'five'	=> t('Basic Configuration'),
									'six'	=> t('Install Complete!'),
									);
					break;

				case 'upgrade':
					$this->setTitle( t('Upgrade Stages') );
					$view = $this->loadView( 'index/upgrade.html' );
					$stages = array(
									t( 'Version Check' ),
									t( 'Security Check' ),
									t( 'Pre-Upgrade Checks' ),
									t( 'Perform Upgrades' ),
									t( 'Upgrade Complete!' ),
									);
					break;

				default:
					return false;
			}
			$view->assign( array(
								'STAGES' 		=> $stages,
								'CONTROLLER'	=> $reqCntrl['controller'],
								));
			return $view->getOutput();
		}

	}

?>
