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

	class Upgrade_controller_stage1 extends Zula_ControllerBase {

		/**
		 * Versions supported by the upgrade wizard
		 */
		protected $supportedVersions = array(
											# Stable versions											
											'2.3.0', '2.3.1', '2.3.2', '2.3.3',
											'2.4.0',

											# Dev versions											
											'2.3.50', '2.3.51', '2.3.52', '2.3.53', '2.3.54', '2.3.55', '2.3.56',
											'2.3.60', '2.3.61', '2.3.62', '2.3.63', '2.3.64',
											'2.3.70', '2.3.71', '2.3.72', '2.3.73',
											'2.3.80', '2.3.81',
											'2.3.90',
											);

		/**
		 * Constructor
		 */
		public function __construct( $moduleDetails, $config, $sector ) {
			parent::__construct( $moduleDetails, $config, $sector );
			$this->_config->update( 'config/title', _PROJECT_NAME.' '._PROJECT_LATEST_VERSION.' '.t('Upgrader') );
		}

		/**
		 * Checks whether there is an existing TangoCMS installation and check
		 * if it is supported by the Upgrader
		 *
		 * @return string
		 */
		public function indexSection() {
			// Check for SQL library
			$_SESSION['upgrade_stage'] = 1;
			if ( Registry::has( 'sql' ) ) {
				if ( in_array( _PROJECT_VERSION, $this->supportedVersions ) ) {
					// Ugly hack for the CLI upgrader sure is ugly.
					if ( PHP_SAPI == 'cli' ) {
						exit(0);
					}
					$_SESSION['upgrade_stage']++;
					$_SESSION['project_version'] = _PROJECT_VERSION;
					// Set the event and zula_redirect to next stage
					$langStr = t('Found version "%1$s" and will upgrade to "%2$s"');
					$this->_event->success( sprintf( $langStr, _PROJECT_VERSION, _PROJECT_LATEST_VERSION ) );
					return zula_redirect( $this->_router->makeUrl( 'upgrade', 'stage2' ) );
				} else {
					// Ugly hack for the CLI upgrader sure is ugly.
					if ( PHP_SAPI == 'cli' ) {
						exit(1);
					}
					$this->setTitle( t('Current Version Unsupported') );
					$view = $this->loadView( 'stage1/not_supported.html' );
					$view->assign( array (
										'CURRENT_VERSION'	=> _PROJECT_VERSION,
										'LATEST_VERSION'	=> _PROJECT_LATEST_VERSION,
										));
				}
			} else {
				$this->setTitle( t('Not Upgradable') );
				$view = $this->loadView( 'stage1/not_upgradable.html' );
			}
			return $view->getOutput();
		}

	}

?>
