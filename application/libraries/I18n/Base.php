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

	abstract class I18n_base extends Zula_LibraryBase {

		/**
		 * The current locale we are using
		 * @var string
		 */
		protected $currentLocale = '';

		/**
		 * All stored text domains
		 * @var array
		 */
		protected $textDomains = array();

		/**
		 * Text domain being used by default
		 * @var string
		 */
		private $DTD = '';

		/**
		 * Constructor function
		 */
		public function __construct() {
		}

		/**
		 * Translates a string in the current domain, or the domain
		 * provided as the second argument.
		 *
		 * @param string $string
		 * @param string $textDomain
		 * @return string
		 */
		abstract public function t( $string, $textDomain=null );

		/**
		 * Plural version of t()
		 *
		 * @param string $string1
		 * @param string $string2
		 * @param int $n
		 * @param string $textDomain	Textdomain to use
		 * @return string
		 */
		abstract public function nt( $string1, $string2, $n, $textDomain=null );

		/**
		 * Binds a domain to a path if it does not already exists.
		 * If it does, then it wont be set again (unless given the
		 * third argument to force it to).
		 *
		 * @param string $domain
		 * @param string $path
		 * @return string|bool
		 */
		abstract public function bindTextDomain( $domain=I18n::_DTD, $path=null, $force=false );

		/**
		 * Sets the default text domain to be using
		 *
		 * @param string $textDomain
		 * @return string|bool
		 */
		abstract public function textDomain( $textDomain=null );

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
		 * Gets the path for a domain if it exists
		 *
		 * @param string $domain
		 * @return string|bool
		 */
		public function getDomainPath( $domain ) {
			if ( $this->textDomainExists( $domain ) ) {
				return $this->textDomains[ $domain ];
			} else {
				trigger_error( 'I18n_base::get_domain_path() domain "'.$domain.'" does not exist', E_USER_NOTICE );
				return false;
			}
		}

		/**
		 * Returns the current locale we are using
		 * @return string
		 */
		public function getCurrentLocale() {
			return $this->currentLocale;
		}

		/**
		 * Sets the locale to use for translations and then returns the locale
		 *
		 * @param string $locale
		 * @return string|bool
		 */
		public function setLocale( $locale ) {
			putenv( 'LANG='.$locale );
			$this->currentLocale = setlocale( LC_ALL, $locale );
			if ( $this->currentLocale === false ) {
				return false;
			}
			return $this->getCurrentLocale();
		}

		/**
		 * Alias to the 'translate' method
		 *
		 * @return string
		 */
		public function _( $string ) {
			return Registry::get( $this->getRegistryName() )->t( $string );
		}

		/**
		 * Alias to 'text_domain' method
		 *
		 * @return string|bool
		 */
		public function setTextDomain( $textDomain=null ) {
			return Registry::get( $this->getRegistryName() )->text_domain( $textDomain );
		}

	}

?>