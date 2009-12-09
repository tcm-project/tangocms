<?php
// $Id: listeners.php 2823 2009-12-03 15:12:40Z alexc $

/**
 * TangoCMS Module (Settings)
 * --- Hooks file for listning to possible events
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Settings
 */

	class Settings_hooks extends Zula_HookBase {

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
		 * Hook: 'router_pre_parse'
		 * Allows for shoter URLs
		 *
		 * @param string $url
		 * @return string
		 */
		public function hookRouterPreParse( $url ) {
			if ( preg_match( '#^(admin/)?settings/(?!(index|update))(.*?)$#i', $url, $matches ) ) {
				return $matches[1].'settings/index/'.$matches[3];
			} else {
				return $url;
			}
		}

	}

?>
