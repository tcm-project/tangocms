<?php
// $Id: stage3.php 2814 2009-12-01 17:19:18Z alexc $

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

	class Upgrade_controller_stage3 extends Zula_ControllerBase {

		/**
		 * Constructor function
		 */
		public function __construct( $moduleDetails, $config, $sector ) {
			parent::__construct( $moduleDetails, $config, $sector );
			$this->_config->update( 'config/title', _PROJECT_NAME.' '._PROJECT_LATEST_VERSION.' '.t('Upgrader') );
		}

		/**
		 * Index section
		 * Pre-Upgrade checks such as if the main config.ini.php
		 * file is writeable
		 *
		 * @return string
		 */
		public function indexSection() {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('Pre Upgrade Checks') );
			if ( !isset( $_SESSION['upgrade_stage'] ) || $_SESSION['upgrade_stage'] !== 3 ) {
				return zula_redirect( $this->_router->makeUrl( 'upgrade', 'stage1' ) );
			}
			/**
			 * All the checks that need to be run, and then actualy run the needed checks
			 */
			$passed = true;
			$tests = array(
							'file'	=> array( $this->_zula->getConfigPath() => '' ),
							'dir'	=> array( $this->_zula->getDir( 'config' ) => '' ),
							);
			foreach( $tests as $type=>&$items ) {
				foreach( $items as $itemName=>$status ) {
					$writable = zula_is_writable( $itemName );
					$items[ $itemName ] = $writable;
					if ( $writable === false ) {
						$passed = false;
					}
				}
			}
			if ( $passed === true ) {
				$this->_event->success( t('The next stage will start the upgrade process which could take some time. Please ensure you have backed up first!') );
				$_SESSION['upgrade_stage']++;
			}
			$view = $this->loadView( 'stage3/checks.html' );
			$view->assign( array(
								'FILE_RESULTS'	=> $tests['file'],
								'DIR_RESULTS'	=> $tests['dir'],
								'PASSED'		=> $passed,
								));
			return $view->getOutput();
		}

	}

?>
