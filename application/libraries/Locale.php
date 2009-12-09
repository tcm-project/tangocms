<?php
// $Id: Locale.php 2768 2009-11-13 18:12:34Z alexc $

/**
 * Zula Framework Locale
 * Language Support/Locale Factory
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Locale
 */

	/**
	 * Translates a string in the current domain, or the domain
	 * provided as the second argument.
	 *
	 * @param string $string
	 * @param string $textDomain
	 * @return string
	 */
	function t( $string, $textDomain=null ) {
		if ( Registry::has( 'locale' ) ) {
			return Registry::get( 'locale' )->t( $string, $textDomain );
		} else {
			trigger_error( 't() no locale engine has currently been loaded', E_USER_NOTICE );
			return $string;
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
	function nt( $string1, $string2, $n, $textDomain=null ) {
		if ( Registry::has( 'locale' ) ) {
			return Registry::get( 'locale' )->nt( $string1, $string2, $n, $textDomain );
		} else {
			trigger_error( 'nt() no locale engine has currently been loaded', E_USER_NOTICE );
			return $string1;
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
	function bind_text_domain( $domain='messages', $path=null, $force=false ) {
		if ( Registry::has( 'locale' ) ) {
			return Registry::get( 'locale' )->bindTextDomain( $domain, $path, $force );
		} else {
			trigger_error( 'bind_text_domain() no locale engine has currently been loaded', E_USER_NOTICE );
			return false;
		}
	}

	/**
	 * Sets the default text domain to be using
	 *
	 * @param string $textDomain
	 * @return string|bool
	 */
	function text_domain( $textDomain=null ) {
		if ( Registry::has( 'locale' ) ) {
			return Registry::get( 'locale' )->textDomain( $textDomain );
		} else {
			trigger_error( 'text_domain() no locale engine has currently been loaded', E_USER_NOTICE );
			return false;
		}
	}

	class Locale {

		/**
		 * Default textdomain used
		 */
		const _DTD = 'zula-base';

		/**
		 * Constructor function
		 */
		private function __construct() {
		}

		/**
		 * Creates the correct Locale engine to use via Factory pattern. All Locale classes
		 * must extend the Locale_base. If the locale engine can not be constructed then it
		 * will revert to a fail-safe translation class that will work on every server
		 *
		 * @param string $engine
		 * @return object
		 */
		static public function factory( $engine ) {
			if ( $engine == 'native_gettext' ) {
				// Change to the newer 'gettext' instead of the older 'native_gettext'
				$engine = 'gettext';
			}
			$engine = 'Locale_'.$engine;
			try {
				$tmpLocale = Registry::get( 'zula' )->loadLib( $engine, 'locale' );
				if ( !($tmpLocale instanceof Locale_base) ) {
					throw new Locale_InvalidEngine( 'Locale engine "'.$engine.'" does not extend Locale_base, reverting to failsafe engine.' );
				}
			} catch ( Locale_InvalidEngine $e ) {
				Registry::get( 'log' )->message( $e->getMessage(), Log::L_WARNING );
				// Revert to the failsafe locale engine
				$tmpLocale = Registry::get( 'zula' )->loadLib( 'Locale_failsafe', 'locale' );
			}
			return $tmpLocale;
		}

	}

?>
