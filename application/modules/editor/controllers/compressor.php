<?php

/**
 * Zula Framework Module (Editor)
 * --- Custom implementation of TinyMCE Compressor
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Editor
 */

	class Editor_controller_compressor extends Zula_ControllerBase {

		/**
		 * Compress all the needed JS files for TinyMCE into one,
		 * and will return it back for TinyMCE to execute.
		 *
		 * The results of this are cached if all data matches up.
		 *
		 * @return string
		 */
		public function indexSection() {
			if ( _AJAX_REQUEST === false ) {
				throw new Module_AjaxOnly;
			}
			$jsFile = 'tinymce-'.zula_hash( implode('|', $this->_input->getAll('get')), null, 'md5' ).'.js';
			// Build path and check if the cached version exists
			$jsFilePath = $this->_zula->getDir( 'tmp' ).'/js/'.$jsFile;
			if ( !file_exists( $jsFilePath ) ) {
				$tinyMcePath = $this->_zula->getDir( 'js' ).'/tinymce';
				$tinyMceLang = $this->_input->get( 'languages' );
				$theme = $this->_input->get( 'themes' );
				// Array of all files that need to be merged together
				$neededFiles = array(
									# Lang files
									'/langs/'.$tinyMceLang.'.js',

									# Theme Files
									'/themes/'.$theme.'/editor_template.js',
									'/themes/'.$theme.'/langs/'.$tinyMceLang.'.js',
									);
				// Plugins
				foreach( explode( ',', $this->_input->get( 'plugins' ) ) as $plugin ) {
					$neededFiles[] = '/plugins/'.$plugin.'/editor_plugin.js';
					$neededFiles[] = '/plugins/'.$plugin.'/langs/'.$tinyMceLang.'.js';
				}
				$content = '';
				foreach( $neededFiles as $file ) {
					$path = $tinyMcePath.$file;
					if ( file_exists( $path ) ) {
						$content .= file_get_contents( $path );
					}
				}
				if ( $this->_input->get( 'core' ) == true ) {
					$content = file_get_contents( $tinyMcePath.'/tiny_mce.js' ).' tinyMCE_GZ.start(); '.$content.' tinyMCE_GZ.end();';
				}
				// Store the file so it can be used later on
				file_put_contents( $jsFilePath, $content );
			}
			zula_readfile( $jsFilePath, 'text/javascript' );
			return false;
		}

	}

?>
