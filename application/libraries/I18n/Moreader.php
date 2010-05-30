<?php

/**
 * Zula Framework
 * Reads a .mo file, see http://www.gnu.org/software/hello/manual/gettext/MO-Files.html
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Robert Clipsham
 * @copyright Copyright (C) 2010 Robert Clipsham
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_I18n
 */

	class I18n_Moreader {

		/**
		 * Resource for the .mo
		 * @var resource
		 */
		protected $resource;

		/**
		 * Array holding the .mo header
		 * @var array
		 */
		protected $header = array();

		/**
		 * Constructor function
		 *
		 * @param string $path - path to .mo files, no trailing /
		 * @param string $domain - text domain to use
		 * @param string $locale - locale to use
		 */
		public function __construct( $path, $domain, $locale ) {
			$moPath = $path.'/'.$locale.'/LC_MESSAGES/'.$domain.'.mo';
			if ( !file_exists( $moPath ) ) {
				throw new MoReader_Exception( 'Unable to open .mo file: File does not exist: '.$moPath );
			}
			$this->resource = fopen( $moPath, 'rb' );
			if ( $this->resource === false ) {
				throw new MoReader_Exception( 'Unable to open .mo file' );
			}
			$this->header = unpack( 'Lmagic/Lrev/LN/LO/LT/LS/LH', fread( $this->resource, 28 ));
			if ( $this->header['magic'] != 0x950412de && $this->header['magic'] != 0xde120495 ) {
				throw new MoReader_Exception( 'Invalid .mo file' );
			} else if ( ($this->header['rev'] >> 16) != 0 && ($this->header['rev'] >> 16) != 1 ) {
				throw new MoReader_Exception( 'Unsupported .mo file version' );
			}
		}

		/**
		 * Read a string from the .mo file at the given index
		 *
		 * @param int $index
		 * @param bool $translation - Get the translation?
		 * @return string
		 */
		protected function getString( $index, $translation=false ) {
			$flag = $translation ? 'T' : 'O';
			fseek( $this->resource, $this->header[ $flag ] + ($index * 8) );
			// Read the length and offset of the given string
			$strInfo = unpack( 'Llength/Loffset', fread( $this->resource, 8 ) );
			// Seek to the offset and read the string
			fseek( $this->resource, $strInfo['offset'] );
			$string = unpack( 'a*string', fread( $this->resource, $strInfo['length'] + 1 ) );
			return $string['string'];
		}

		/**
		 * Perform a binary search on the .mo file to find the
		 * translated string.
		 *
		 * @param string $string - String to translate
		 * @return string
		 */
		public function t( $string ) {
			$low = 0;
			$high = $this->header['N'] - 1;
			do {
				$mid = floor( ( $low + $high ) / 2 );
				if ( $string > $this->getString( $mid ) ) {
					$low = $mid + 1;
				} else {
					$high = $mid - 1;
				}
			} while( $string != $this->getString( $mid ) && $low <= $high );

			if ( $string == $this->getString( $mid ) ) {
				return $this->getString( $mid, true );
			}
			return $string;
		}

		/**
		 * Destructor function
		 */
		public function __destruct() {
			fclose( $this->resource );
		}

	}

?>
