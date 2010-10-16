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

	class Upgrade_controller_version extends Zula_ControllerBase {

		/**
		 * Versions supported by the upgrade wizard
		 */
		protected $supportedVersions = array(
											# Stable versions
											'2.3.0', '2.3.1', '2.3.2', '2.3.3',
											'2.4.0',
											'2.5.0', '2.5.1', '2.5.2', '2.5.3', '2.5.4', '2.5.5',

											# Dev versions
											'2.3.50', '2.3.51', '2.3.52', '2.3.53', '2.3.54', '2.3.55', '2.3.56',
											'2.3.60', '2.3.61', '2.3.62', '2.3.63', '2.3.64',
											'2.3.70', '2.3.71', '2.3.72', '2.3.73',
											'2.3.80', '2.3.81',
											'2.3.90',

											'2.4.50', '2.4.51', '2.4.52', '2.4.53', '2.4.54', '2.4.55',
											'2.4.90',

											'2.5.50', '2.5.51', '2.5.52', '2.5.53', '2.5.54', '2.5.55', '2.5.56',
											'2.5.60', '2.5.61', '2.5.62', '2.5.63', '2.5.64',
											);

		/**
		 * Constructor
		 *
		 * @return object
		 */
		public function __construct( $moduleDetails, $config, $sector ) {
			parent::__construct( $moduleDetails, $config, $sector );
			$this->_config->update( 'config/title', _PROJECT_NAME.' '._PROJECT_LATEST_VERSION.' '.t('Upgrader') );
		}

		/**
		 * Check if the currently installed version is supported
		 * by this upgrader
		 *
		 * @return bool|string
		 */
		public function indexSection() {
			$_SESSION['upgradeStage'] = 1;
			if ( Registry::has( 'sql' ) && in_array( _PROJECT_VERSION, $this->supportedVersions ) ) {
				++$_SESSION['upgradeStage'];
				$_SESSION['project_version'] = _PROJECT_VERSION;
				// Set the event and redirect to next stage
				$langStr = t('Found version "%1$s" and will upgrade to "%2$s"');
				$this->_event->success( sprintf( $langStr, _PROJECT_VERSION, _PROJECT_LATEST_VERSION ) );
				return zula_redirect( $this->_router->makeUrl('upgrade', 'security') );
			}
			$langStr = t('Version %s is not supported by this upgrader');
			$this->_event->error( sprintf( $langStr, _PROJECT_VERSION ) );
			if ( $this->_zula->getMode() == 'cli' ) {
				exit( 3 );
			} else {
				return zula_redirect( $this->_router->makeUrl('index') );
			}
		}

	}

?>
