<?php
// $Id: Ini.php 2814 2009-12-01 17:19:18Z alexc $

/**
 * Zula Framework Configuration INI
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Config
 */

	// Exceptions
	class Config_ini_FileNoExist extends Exception {}
	class Config_ini_FileNotWriteable extends Exception {}

	class Config_ini extends Config_base {

		/**
		 * The file we are loading the ini settings from
		 * @var string
		 */
		protected $iniFile = null;

		/**
		 * Parses the configuration INI file
		 *
		 * @param string $iniFile
		 * @return array
		 */
		public function load( $iniFile ) {
			if ( !file_exists( $iniFile ) || !is_readable( $iniFile ) ) {
				throw new Config_ini_FileNoExist( 'configuration file "'.$iniFile.'" does not exist or is not readable' );
			}
			$this->iniFile = $iniFile;
			return $this->setConfigValues( parse_ini_file( $iniFile, true ) );
		}

		/**
		 * Returns the file that is being used
		 *
		 * @return string
		 */
		public function getFile() {
			return $this->iniFile;
		}

		/**
		 * Rewrites the configuration ini file back, leaving it as
		 * in-take as possible (ie, keeping all comments) in place
		 *  and same sort of structure.
		 *
		 * @return bool
		 */
		public function writeIni() {
			if ( !zula_is_writable( $this->iniFile ) ) {
				throw new Config_ini_FileNotWriteable( $this->iniFile.' is not writeable' );
			}
			$iniContent = '';
			/**
			 * Open the file and read line by line, rewriting it as
			 * it goes a long
			 */
			$fHandle = fopen( $this->iniFile, 'rb' );
			$sections = array(); # Store the sections and values which have been written
			while( !feof( $fHandle ) ) {
				$line = trim( fgets( $fHandle ) )."\n";
				if ( zula_substr( $line, 0, 1 ) == ';' ) {
					// Line is a comment
					$iniContent .= $line;
				} else if ( zula_substr( $line, 0, 1 ) == '[' ) {
					preg_match( '#\[(.*?)\]#', $line, $matches );
					try {
						$values = $this->get( $matches[1] );
						$sections[] = $matches[1];
						$iniContent .= $matches[0]."\n";
						foreach( $values as $key=>$val ) {
							if ( preg_match( '#[^A-Z0-9_\-./]#i', $val ) ) {
								$val = '"'.$val.'"';
							}
							$iniContent .= $key.' = '.$val."\n";
						}
						// Add a spacer to the bottom
						$iniContent .= "\n";
					} catch ( Config_KeyNoExist $e ) {
						continue;
					}
				}
			}
			/**
			 * Add on the extra values that need to be added to the ini file
			 */
			foreach( $this->getAll() as $section=>$values ) {
				if ( empty( $values ) ) {
					continue; # No need to add empty sections in
				} else if ( !in_array( $section, $sections ) ) {
					$iniContent .= '['.$section."]\n";
					foreach( $values as $key=>$val ) {
						$val = (string) $val;
						if ( preg_match( '#[^A-Z0-9_\-./]#i', $val ) ) {
							$val = '"'.$val.'"';
						}
						$iniContent .= $key.' = '.$val."\n";
					}
				}
			}
			file_put_contents( $this->iniFile, trim($iniContent) );
			return true;
		}

	}

?>
