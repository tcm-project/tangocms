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

	class I18n_Php extends I18n_base {

		/**
		 * An array of moReaders
		 * @var array
		 */
		protected $moReaders = array();

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
					$this->moReaders[ $textDomain ] = new I18n_Moreader( $path, $textDomain, $locale );
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

	}

?>
