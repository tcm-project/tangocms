<?php

/**
 * Zula Framework Module
 * Hooks file for listening to possible events
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Theme
 */

	class Theme_Hooks extends Zula_HookBase {

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
		 * hook: bootstrap_pre_request
		 *
		 * @return null
		 */
		public function hookBootstrapPreRequest() {
			if ( $this->_config->get( 'theme/allow_user_override' ) ) {
				$userDetails = $this->_session->getUser();
				if ( !empty( $userDetails['theme'] ) && Theme::exists( $userDetails['theme'] ) ) {
					$this->_config->update( 'theme/'.$this->_router->getSiteType().'_default', $userDetails['theme'] );
				}
			}
		}

	}

?>
