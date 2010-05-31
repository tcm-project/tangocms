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

	}

?>
