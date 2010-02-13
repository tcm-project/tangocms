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
				foreach( new DirectoryIterator( $this->_zula->getDir( 'js' ).'/tinymce/plugins' ) as $file ) {
					if ( substr( $file, 0, 1 ) != '.' && $file->isDir() ) {
						$tinyMcePlugins[] = $file->getFileName();
					}
				}
				$tinyMcePlugins = implode( ',', $tinyMcePlugins );
				/**
				 * Load TinyMCE, though right at the start as TinyMCE is a bitch
				 * about where it needs to be loaded, called, and even file name.
				 *
				 * This is why we bypass the Theme::addJsFile() method.
				 */
				$this->_theme->addHead( 'js', array(),
										'var tcmEditor = {defaultFormat: "'.Editor::defaultFormat().'", tinymcePlugins: "'.$tinyMcePlugins.'"};'
									  );
				$this->_theme->addJsFile( 'tinymce/jquery.tinymce.js' );
				$this->_theme->addJsFile( 'js/init.js', true, 'editor' );
			}
			return true;
		}

		/**
		 * Hook: router_pre_parse
		 *
		 * @return string
		 */
		public function hookRouterPreParse( $url ) {
			if ( preg_match( '#tinymce/tiny_mce_gzip.php$#i', $url ) ) {
				$url = 'editor/compressor';
			}
			return $url;
		}

		/**
		 * Listener for 'view_output' to check if the editor
		 * needs to be loaded. If 'editor_body' is found then
		 * it will be loaded
		 *
		 * @param string $text
		 * @praam string $viewFile
		 * @param string $module
		 * @return string
		 */
		public function hookViewOutput( $text, $viewFile, $module ) {
			if ( $this->loadEditor === false ) {
				// editor_body will never be at position 0, so it does not need to check for 0
				$this->loadEditor = (bool) strpos( $text, 'editor_body' );
			}
			return $text;
		}

	}

?>
