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

	class Upgrade_controller_stage5 extends Zula_ControllerBase {

		/**
		 * Constructor
		 */
		public function __construct( $moduleDetails, $config, $sector ) {
			parent::__construct( $moduleDetails, $config, $sector );
			$this->_config->update( 'config/title', _PROJECT_NAME.' '._PROJECT_LATEST_VERSION.' '.t('Upgrader') );
		}

		/**
		 * Displays the upgrade successful message
		 *
		 * @return string
		 */
		public function indexSection() {
			if ( !isset( $_SESSION['upgrade_stage'] ) || $_SESSION['upgrade_stage'] !== 5 ) {
				return zula_redirect( $this->_router->makeUrl( 'upgrade', 'stage1' ) );
			}
			$view = $this->loadView( 'stage5/upgrade_successful.html' );
			$view->assign( array(
								'PROJECT_VERSION' => $_SESSION['project_version'],
								));
			return $view->getOutput();
		}

	}

?>
