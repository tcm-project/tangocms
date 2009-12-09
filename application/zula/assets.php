<?php
// $Id: assets.php 2768 2009-11-13 18:12:34Z alexc $

/**
 * Zula Framework Assets Dispatcher
 * --- Handles the default hard-coded 'assets/v/' route for getting JS/CSS/etc files
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Assets
 */

	$assetPath = substr( $rawRequestPath, 9 );
	if ( $assetPath !== false && ($slashPos = strpos($assetPath, '/')) !== false ) {
		// Get module from the path
		$module = preg_replace( '#[^A-Z0-9_\-]+#i', '', substr($assetPath, 0, $slashPos) );
		$path = substr( $assetPath, $slashPos+1 );
		if ( $path !== false ) {
			/**
			 * Build both the path and real path to the asset, comparing that to what was
			 * provided. Basically a similar functionaility to openbase_dir I guess.
			 */
			$assetDir = Module::getDirectory().'/'.$module.'/assets';
			$assetPath = $assetDir.'/'.$path;
			if ( strpos( realpath($assetPath), realpath($assetDir) ) === 0 && is_readable( $assetPath ) ) {
				// Set all of the headers needed and output the file
				switch( pathinfo($assetPath, PATHINFO_EXTENSION) ) {
					case 'js':
						$mime = 'text/javascript; charset=utf-8';
						break;
					case 'css':
						$mime = 'text/css; charset=utf-8';
						break;
					default:
						$mime = zula_get_file_mime( $assetPath );
						if ( $mime === false ) {
							$mime = 'text/plain';
						}
				}
				return (bool) zula_readfile( $assetPath, $mime );				
			}
		}
	}
	// Will only ever occur if something goes wrong up there.
	header( 'HTTP/1.1 404 Not Found', true, 404 );
	return false;

?>