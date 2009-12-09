<?php

/**
 * Zula Framework Module (Editor)
 * --- Display editor preview
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Editor
 */

	class Editor_controller_preview extends Zula_ControllerBase {

		/**
		 * Index section, gets output of the editor
		 *
		 * @return string|string
		 */
		public function indexSection() {
			if ( _AJAX_REQUEST === false ) {
				throw new Module_AjaxOnly;
			}
			try {
				$editor = new Editor( $this->_input->post( 'body' ) );
				$editor->preParse();
				// Create a new view to load the default tags
				$view = new View;
				$view->loadString( $editor->parse() );
				return $view->getOutput( true );
			} catch ( Exception $e ) {
				return false;
			}
		}

	}

?>
