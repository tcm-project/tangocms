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

	class Upgrade_controller_checks extends Zula_ControllerBase {

		/**
		 * Constructor
		 *
		 * @return object
		 */
		public function __construct( $moduleDetails, $config, $sector ) {
			parent::__construct( $moduleDetails, $config, $sector );
			$this->_config->update( 'config/title', sprintf('%s %s upgrader', _PROJECT_NAME, _PROJECT_LATEST_VERSION) );
		}

		/**
		 * Pre-upgrade checks to ensure the environment is how we
		 * require it.
		 *
		 * @return bool|string
		 */
		public function indexSection() {
			$this->setTitle( t('Pre-upgrade checks') );
			if (
				$this->_zula->getMode() != 'cli' &&
				(!isset( $_SESSION['upgradeStage'] ) || $_SESSION['upgradeStage'] !== 3)
			) {
				return zula_redirect( $this->_router->makeUrl('upgrade', 'version') );
			}
			/**
			 * All the checks that need to be run, and then actualy run the needed checks
			 */
			$tests = array(
							'files'	=> array( $this->_zula->getConfigPath() => '' ),
							'dirs'	=> array( $this->_zula->getDir('config') => '' ),
							);
			$passed = true;
			foreach( $tests as $type=>&$items ) {
				foreach( $items as $itemName=>$status ) {
					$writable = zula_is_writable( $itemName );
					$items[ $itemName ] = $writable;
					if ( $writable === false ) {
						$passed = false;
					}
				}
			}
			if ( $passed ) {
				if ( isset( $_SESSION['upgradeStage'] ) ) {
					++$_SESSION['upgradeStage'];
				}
				$this->_event->success( t('Pre-upgrade checks were successful') );
				return zula_redirect( $this->_router->makeUrl('upgrade', 'migrate') );
			} else {
				if ( $this->_zula->getMode() == 'cli' ) {
					$this->_zula->setExitCode( 3 );
				}
				$this->_event->error( t('Sorry, your server environment does not meet our requirements') );
				$view = $this->loadView( 'checks'.($this->_zula->getMode() == 'cli' ? '-cli.txt' : '.html') );
				$view->assign( array(
									'file_results'	=> $tests['files'],
									'dir_results'	=> $tests['dirs'],
									'passed'		=> $passed,
									));
				return $view->getOutput();
			}
		}

	}

?>
