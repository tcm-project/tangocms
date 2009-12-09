<?php

/**
 * Zula Framework Module (contact)
 * --- Hooks file for listning to possible events
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_contact
 */

	class Contact_hooks extends Zula_HookBase {

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
		 * Hook: error_useful_links
		 * Adds in a link to the Contact module, when a 404/403 error occurs
		 *
		 * @return array
		 */
		public function hookErrorUsefulLinks( $errCode ) {
			return array( array(
								'url'	=> $this->_router->makeUrl( 'contact' ),
								'title'	=> t('Contact', _PROJECT_ID.'-contact'),
								));
		}

		/**
		 * Hook: contact_display_modes
		 * Gets all display modes that this module has
		 *
		 * @return array
		 */
		public function hookContactDisplayModes() {
			return array(
						'contactform'	=> t('Contact Form', _PROJECT_ID.'-contact'),
						);
		}

		/**
		 * Hook: contact_resolve_mode
		 * Resolves a given Controller, Section and config data to an
		 * avaible display mode offered.
		 *
		 * @param string $cntrlr
		 * @param string $sec
		 * @param array $config
		 * @return string
		 */
		public function hookContactResolveMode( $cntrlr, $sec, $config ) {
			return 'contactform';
		}

		/**
		 * Hook: contact_display_mode_config
		 * Returns HTML (commonly a table) to configure a display mode
		 *
		 * @param string $mode
		 * @return string
		 */
		public function hookContactDisplayModeConfig( $mode ) {
			$view = new View( 'layout_edit/contact_form.html', 'contact' );
			$view->assign( array(
								'FORMS'	=> $this->_model( 'contact', 'contact' )->getAllForms(),
								));
			return $view->getOutput();
		}

	}

?>
