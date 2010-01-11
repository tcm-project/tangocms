<?php

/**
 * Zula Framework Module (Editor)
 * --- Hooks file for listning to possible events
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Editor
 */

	class Editor_hooks extends Zula_HookBase {

		/**
		 * Flag to toggle if Editor should be loaded
		 * @var bool
		 */
		protected $loadEditor = false;

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
		 * Listener for 'bootstrap_loaded' hook.
		 * Adds in required JS files for the editor to display
		 *
		 * @return array
		 */
		public function hookBootstrapLoaded( $ajaxRequest ) {
			if ( $this->loadEditor === true && Registry::has( 'theme' ) ) {
				// Content to add in some JavaScript tags.
				$this->_theme->addHead( 'js', array(),
										'var CKEDITOR_BASEPATH = "'.$this->_zula->getDir('js', true).'/ckeditor/"; '.
										'var CKEDITOR_UPLOAD_URL = "'.$this->_router->makeUrl('editor', 'upload').'"; '.
										'var tcmEditor = {defaultFormat: "'.Editor::defaultFormat().'"};'
									  );
				$usedEditors = Editor::usedEditors();
				if ( (empty( $usedEditors ) && Editor::defaultFormat() == 'html') || in_array( 'html', $usedEditors ) ) {
					// Load CKEditor
					$this->_theme->addJsFile( 'ckeditor/ckeditor.js' );
				}
				$this->_theme->addJsFile( 'js/init.js', true, 'editor' );
			}
			return true;
		}

		/**
		 * Listener for 'view_output' to check if the editor
		 * needs to be loaded. If 'richTextEditor' is found then
		 * it will be loaded
		 *
		 * @param string $text
		 * @praam string $viewFile
		 * @param string $module
		 * @return string
		 */
		public function hookViewOutput( $text, $viewFile, $module ) {
			if ( $this->loadEditor === false ) {
				// richTextEditor will never be at position 0, so it does not need to check for 0
				$this->loadEditor = (bool) strpos( $text, 'richTextEditor' );
			}
			return $text;
		}

	}

?>
