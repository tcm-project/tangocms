<?php
// $Id: listeners.php 2768 2009-11-13 18:12:34Z alexc $

/**
 * Zula Framework Module (shareable)
 * --- Hooks file for listning to possible events
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Shareable
 */

	class Shareable_Hooks extends Zula_HookBase {

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
		 * hook: module_output_bottom
		 * Adds in the 'Share This'/Site Submission to bottom of certain
		 * content types.
		 *
		 * @param array $msc
		 * @param int $contentType
		 * @param string $sector
		 * @param string $title
		 * @return mixed
		 */
		public function hookModuleOutputBottom( array $mcs, $contentType, $sector, $title ) {
			if ( 
				$contentType & Zula_ControllerBase::_OT_CONTENT_DYNAMIC &&
				!($contentType & Zula_ControllerBase::_OT_CONFIG)
			) {
				$sites = $this->_model( 'shareable', 'shareable' )->getSites( Shareable_Model::_ENABLED );
				if ( $sites ) {
					$view = new View( 'main.html', 'shareable' );
					$view->assign( array(
										'SITES'	=> $sites,
										'TITLE'	=> $title,
										));
					return $view->getOutput();
				}
			}
		}

	}

?>
