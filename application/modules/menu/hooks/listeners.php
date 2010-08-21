<?php

/**
 * Zula Framework Module (Menu)
 * --- Hooks file for listning to possible events
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Menu
 */

	class Menu_hooks extends Zula_HookBase {

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
		 * Hook: menu_display_modes
		 * Gets all display modes that this module has
		 *
		 * @return array
		 */
		public function hookMenuDisplayModes() {
			return array(
						'displaymenu'	=> t('Display menu', _PROJECT_ID.'-menu'),
						);
		}

		/**
		 * Hook: menu_resolve_mode
		 * Resolves a given Controller, Section and config data to an
		 * avaible display mode offered.
		 *
		 * @param string $cntrlr
		 * @param string $sec
		 * @param array $config
		 * @return string
		 */
		public function hookMenuResolveMode( $cntrlr, $sec, $config ) {
			return 'displaymenu';
		}

		/**
		 * Hook: menu_display_mode_config
		 * Returns HTML (commonly a table) to configure a display mode
		 *
		 * @param string $mode
		 * @return string
		 */
		public function hookMenuDisplayModeConfig( $mode ) {
			$view = new View( 'layout_edit/display_menu.html', 'menu' );
			$view->assign( array(
								'CATEGORIES' => $this->_model( 'menu', 'menu' )->getAllCategories(),
								));
			return $view->getOutput();
		}

	}

?>
