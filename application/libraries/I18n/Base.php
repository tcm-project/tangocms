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
		protected $currentLocale = 'en_US.UTF-8';

		/**
		 * All stored text domains
		 * @var array
		 */
		protected $textDomains = array();

		/**
		 * Text domain being used by default
		 * @var string
		 */
		private $currentTextDomain = I18n::_DTD;

		/**
		 * Binds the default text domain and sets that as the one to use
		 *
		 * @return object
		 */
		public function __construct() {
			$this->bindTextDomain();
			$this->textDomain( I18n::_DTD );
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
		 * @param string $textDomain
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
		public function bindTextDomain( $domain=I18n::_DTD, $path=null, $force=false ) {
			if ( !$this->textDomainExists( $domain ) || ($this->textDomainExists( $domain ) && $force) ) {
				$this->textDomains[ $domain ] = $path ? $path : $this->_zula->getDir( 'locale' );
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
				$this->currentTextDomain = $textDomain;
			}
			return $this->currentTextDomain;
		}

		/**
		 * Checks if a text domain name exists
		 *
		 * @param string $domain
		 * @return bool
		 */
		public function textDomainExists( $domain ) {
			return isset( $this->textDomains[$domain] );
		}

		/**
		 * Gets the path for a domain if it exists
		 *
		 * @param string $domain
		 * @return string|bool
		 */
		public function getDomainPath( $domain ) {
			return isset( $this->textDomains[$domain] ) ? $this->textDomains[ $domain ] : false;
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
			$this->currentLocale = $locale;
			return $this->currentLocale;
		}

		/**
		 * Gets all available i18n locales that are present
		 *
		 * @return array
		 */
		public function getAvailableLangs() {
			$langs = array();
			$glob = glob( $this->_zula->getDir( 'locale' ).'/*/settings.json' );
			if ( $glob ) {
				foreach( $glob as $file ) {
					$json = json_decode( file_get_contents($file) );
					$langs[ $json->locale ] = $json->name;
				}
			}
			return $langs;
		}

	}

?>