<?php

/**
 * Zula Framework
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_I18n
 */

	class I18n_gettext extends I18n_base {

		/**
		 * Constructor function
		 * Checks if the gettext extension is loaded, if so then set
		 * the locale if there is one provided, and set textdomain to use
		 *
		 * @return object
		 */
		public function __construct() {
			if ( !extension_loaded( 'gettext' ) || !function_exists( 'gettext' ) ) {
				throw new I18n_InvalidEngine( 'server does not have gettext extension loaded - unable to use gettext i18n engine' );
			}
			parent::__construct();
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
			if ( empty( $textDomain ) ) {
				return gettext( $string );
			} else {
				return dgettext( $textDomain, $string );
			}
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
			if ( empty( $textDomain ) ) {
				return ngettext( $string1, $string2, $n );
			} else {
				return dngettext( $textDomain, $string1, $string2, $n );
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
			if ( !$this->textDomainExists( $domain ) || ($this->textDomainExists( $domain ) && $force) ) {
				if ( $path == false ) {
					$path = $this->_zula->getDir( 'locale' );
				}
				$this->textDomains[ $domain ] = bindtextdomain( $domain, $path );
			}
			return $this->textDomains[ $domain ];
		}

		/**
		 * Sets the default text domain to be using, or if passed an empty
		 * value the current text domain will be returned.
		 *
		 * @param string $textDomain
		 * @return string|bool
		 */
		public function textDomain( $textDomain=null ) {
			if ( $textDomain ) {
				$this->DTD = textdomain( $textDomain );
				return $this->DTD;
			} else {
				return textdomain( null );
			}
		}

		/**
		 * Sets the locale to use for translations and then returns the locale
		 *
		 * @param string $locale
		 * @return string|bool
		 */
		public function setLocale( $locale ) {
			setlocale( LC_ALL, $locale );
			return parent::setLocale( $locale );
		}

	}

?>