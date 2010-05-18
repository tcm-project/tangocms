<?php

/**
 * Zula Framework
 *
 * Provides some functions that are used incase the selected i18n engine could
 * not be used for some reason. This class will give a fail-safe approach
 * and will basically just return the value that was supposed to be translated
 * straight back without doing any translating ... hey, I did say it was fail-safe ;)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_I18n
 */

	class I18n_failsafe extends I18n_base {

		/**
		 * Constructor function
		 */
		public function __construct() {
			$this->_log->message( 'Using fail-safe i18n engine', Log::L_DEBUG );
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
			return $string;
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
			if ( trim( $textDomain ) ) {
				$this->textDomain( $textDomain );
			}
			if ( $n == 1 ) {
				return $string1;
			} else {
				return $string2;
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
			if ( !trim( $domain ) ) {
				$domain = I18n::_DTD;
			}
			if ( empty( $path ) ) {
				$path = $this->_zula->getDir( 'locale' );
			}
			// If it exists then don't set it again unless we have to
			if ( $this->textDomainExists( $domain ) && $force === false ) {
				return $this->getDomainPath( $domain );
			} else if ( !$this->textDomainExists( $domain ) || ($this->textDomainExists( $domain ) && $force === true) ) {
				$this->textDomains[ $domain ] = $path;
			}
			return $this->textDomains[ $domain ];
		}

		/**
		 * Sets the default text domain to be using
		 *
		 * @param string $textDomain
		 * @return string|bool
		 */
		public function textDomain( $textDomain=null ) {
			$this->DTD = $textDomain;
			return $textDomain;
		}

	}

?>