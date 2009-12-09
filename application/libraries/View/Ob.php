<?php
// $Id: Ob.php 2768 2009-11-13 18:12:34Z alexc $

/**
 * Zula Framework View OB
 * Creates a sort of ... closed environment to run the PHP code that is in the view
 * views. It also provides Helper methods to ease common tasks
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_View
 */

	class View_OB extends Zula_LibraryBase {

		/**
		 * Every tag to extract into a php variable
		 * @var array
		 */
		protected $tags = array();

		/**
		 * The path to the view file
		 * @var string
		 */
		protected $viewPath = '';

		/**
		 * The module this view file is for
		 * @var string
		 */
		protected $module = null;

		/**
		 * Helper objects used in all view files
		 * @var array
		 */
		static protected $helpers = array();

		/**
		 * Constructor function
		 * Takes in the tags that will later be converted to PHP vars
		 * and also the path to the view file
		 *
		 * @param array $tags
		 * @param string $viewPath
		 * @param string $module
		 * @return object
		 */
		public function __construct( $tags, $viewPath, $module=null ) {
			$this->tags = $tags;
			$this->viewPath = $viewPath;
			$this->module = $module;
		}

		/**
		 * Sets the PHP variables, then includes the view file
		 * without output buffering so the PHP code output can be captured
		 *
		 * @return string
		 */
		public function getOutput() {
			extract( $this->tags );
			ob_start();
				require $this->viewPath;
			return ob_get_clean();
		}

		/**
		 * Returns the full path for the current view file, or work out
		 * the realpath from the provided relative path given.
		 *
		 * @param string $viewFile 	Path relative to curernt view file
		 * @return string
		 */
		protected function getPath( $viewFile=null ) {
			$path = trim($viewFile) ? dirname( $this->viewPath ).'/'.$viewFile : $this->viewPath;
			return realpath( $path );
		}

		/**
		 * Allows easier access to icons for modules, as it will add
		 * in the correct directory prefix to use as well
		 *
		 * @param string $icon
		 * @param bool $forHtml
		 * @return string
		 */
		protected function getIcon( $icon, $forHtml=true ) {
			return zula_get_icon( $icon, $this->module, $forHtml );
		}

		/**
		 * Provides access to all available helpers
		 *
		 * @param string $helper
		 * @return object
		 */
		protected function _helper( $helper ) {
			if ( !isset( self::$helpers[ $helper ] ) ) {
				$class = 'View_Helpers_'.zula_camelise( $helper );
				if ( !class_exists( $class ) ) {
					throw new View_HelperNoExist( 'View unable to load helper "'.$helper.'" as it does not exist' );
				}
				self::$helpers[ $helper ] = new $class;
			}
			return self::$helpers[ $helper ];
		}

	}

?>