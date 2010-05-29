<?php

/**
 * Zula Framework
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Robert Clipsham
 * @copyright Copyright (C) 2010 Robert Clipsham
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_I18n
 */

	class MoReaderException extends Zula_Exception {}

	/**
	 * Read a .mo file
	 *
	 * See http://www.gnu.org/software/hello/manual/gettext/MO-Files.html
	 * for file format
	 */
	class MoReader {

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
			$moPath = $path . '/' . $locale . '/LC_MESSAGES/' . $domain . '.mo';
			if ( !file_exists( $moPath ) ) {
				throw new MoReaderException( 'Unable to open .mo file: File does not exist: '. $moPath );
			}
			$this->resource = fopen( $moPath, 'rb' );
			if ( $this->resource === false ) {
				throw new MoReaderException( 'Unable to open .mo file' );
			}
			$this->header = unpack( 'Lmagic/Lrev/LN/LO/LT/LS/LH', fread( $this->resource, 28 ));
		 	if ( $this->header['magic'] != 0x950412de && $this->header['magic'] != 0xde120495 ) {
				throw new MoReaderException( 'Invalid .mo file' );
			}
			if ( ($this->header['rev'] >> 16) != 0 && ($this->header['rev'] >> 16) != 1 ) {
				throw new MoReaderException( 'Unsupported .mo file version' );
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

	class I18n_php extends I18n_base {

		/**
		 * An array of moReaders
		 * @var array
		 */
		protected $moReaders = array();

		/**
		 * Constructor function
		 */
		public function __construct() {
			if ( $this->_config->has( 'locale/default' ) ) {
				$this->setLocale( $this->_config->get( 'locale/default' ) );
			}
			$this->bindTextDomain();
			$this->textDomain( I18n::_DTD );
		}

		/**
		 * Set the current locale
		 *
		 * @param string $locale
		 * @return string
		 */
		public function setLocale( $locale ) {
			return $this->currentLocale = $locale;
		}

		/**
		 * Translates a string in the current domain, or the domain
		 * provided as the second argument.
		 *
		 * @param string $string
		 * @param string $textDomain
		 * @return string
		 */
		public function t( $string, $textDomain=null ) {
			if ( $this->getCurrentLocale() == null ) {
				return $string;
			}
			if ( empty( $textDomain ) ) {
				$textDomain = $this->DTD;
			}
			if ( !isset( $this->moReaders[ $textDomain ] ) ) {
				try {
					$path = $this->getDomainPath( $textDomain );
					$locale = substr( $this->getCurrentLocale(), 0, 5 ); 
					$this->moReaders[ $textDomain ] = new MoReader( $path, $textDomain, $locale );
				} catch ( Exception $e ) {
					$this->_log->message( $e->getMessage(), Log::L_WARNING );
					return $string;
				}
			}
			return $this->moReaders[ $textDomain ]->t( $string );
		}

		/**
		 * Plural version of t()
		 *
		 * @param string $string1
		 * @param string $string2
		 * @param int $n
		 * @param string $textDomain	Textdomain to use
		 * @return string
		 */
		public function nt( $string1, $string2, $n, $textDomain=null ) {
			if ( $n == 1 ) {
				return $this->t( $string1, $textDomain );
			} else {
				return $this->t( $string2, $textDomain );
			}
		}

		/**
		 * Binds a domain to a path if it does not already exists.
		 * If it does, then it wont be set again (unless given the
		 * third argument to force it to).
		 *
		 * @param string $domain
		 * @param string $path
		 * @return string|bool
		 */
		public function bindTextDomain( $domain=I18n::_DTD, $path=null, $force=false ) {
			if ( empty( $domain ) ) {
				$domain = I18n::_DTD;
			}
			if ( empty( $path ) ) {
				$path = $this->_zula->getDir( 'locale' );
			}
			// If it exists then don't set it again unless we have to
			if ( $this->textDomainExists( $domain ) && $force == false ) {
				return $this->getDomainPath( $domain );
			} else if ( !$this->textDomainExists( $domain ) || ($this->textDomainExists( $domain ) && $force) ) {
				$this->textDomains[ $domain ] = $path;
			}
			$this->_log->message( 'I18n_php::bindTextDomain() added domain "'.$domain.'" with path "'.$path.'"', Log::L_DEBUG, __FILE__, __LINE__ );
			return $this->textDomains[ $domain ];
		}

		/**
		 * Sets the default text domain to be using or
		 * returns the current if null is passed
		 *
		 * @param string $textDomain
		 * @return string|bool
		 */
		public function textDomain( $textDomain=null ) {
			if ( $this->DTD == $textDomain ) {
				return $this->DTD;
			} else {
				$this->DTD = $textDomain;
				$this->_log->message( 'I18n_php::textDomain() set text domain to "'.$this->DTD.'"', Log::L_DEBUG );
				return $this->DTD;
			}
		}

		/**
		 * Checks if a text domain name exists
		 *
		 * @param string $domain
		 * @return bool
		 */
		public function textDomainExists( $domain ) {
			return isset( $this->textDomains[ $domain ] );
		}

		/**
		 * Returns the current locale we are using
		 * @return string
		 */
		public function getCurrentLocale() {
			return $this->currentLocale;
		}

	}

?>
