<?php

/**
 * Zula Framework Module
 * Hooks file for listening to possible events
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Page
 */

	class Page_hooks extends Zula_HookBase {

		/**
		 * Constructor
		 * Calls the parent constructor to register the methods
		 *
		 * @return object
		 */
		public function __construct() {
			parent::__construct( $this );
		}

		/**
		 * Hook: page_display_modes
		 * Gets all display modes that this module has
		 *
		 * @return array
		 */
		public function hookPageDisplayModes() {
			return array(
						'displaypage'	=> t('Display page', _PROJECT_ID.'-page'),
						);
		}

		/**
		 * Hook: page_resolve_mode
		 * Resolves a given Controller, Section and config data to an
		 * avaible display mode offered.
		 *
		 * @param string $cntrlr
		 * @param string $sec
		 * @param array $config
		 * @return string
		 */
		public function hookPageResolveMode( $cntrlr, $sec, $config ) {
			return 'displaypage';
		}

		/**
		 * Hook: page_display_mode_config
		 * Returns HTML (commonly a table) to configure a display mode
		 *
		 * @param string $mode
		 * @return string
		 */
		public function hookPageDisplayModeConfig( $mode ) {
			$view = new View( 'layout_edit/display_page.html', 'page' );
			$view->assign( array(
								'PAGES'	=> $this->_model( 'page', 'page' )->getAllPages(),
								));
			return $view->getOutput();
		}

	}

?>
