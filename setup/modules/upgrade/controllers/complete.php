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

	class Upgrade_controller_complete extends Zula_ControllerBase {

		/**
		 * Constructor
		 *
		 * @return object
		 */
		public function __construct( $moduleDetails, $config, $sector ) {
			parent::__construct( $moduleDetails, $config, $sector );
			$this->_config->update( 'config/title',  sprintf('%s %s upgrader', _PROJECT_NAME, _PROJECT_LATEST_VERSION) );
		}

		/**
		 * Displays the upgrade successful message
		 *
		 * @return string
		 */
		public function indexSection() {
			$this->setTitle( t('Upgrade complete!') );
			if ( !isset( $_SESSION['upgradeStage'] ) || $_SESSION['upgradeStage'] !== 5 ) {
				return zula_redirect( $this->_router->makeUrl('upgrade', 'version') );
			} else {
				$view = $this->loadView( 'complete.html' );
				$view->assign( array('project_version' => $_SESSION['project_version']) );
				return $view->getOutput();
			}
		}

	}

?>
