<?php

/**
 * Zula Framework I18n
 * i18n factory
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_I18n
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
		if ( Registry::has( 'i18n' ) ) {
			return Registry::get( 'i18n' )->t( $string, $textDomain );
		} else {
			trigger_error( 't() no i18n engine has currently been loaded', E_USER_NOTICE );
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
		if ( Registry::has( 'i18n' ) ) {
			return Registry::get( 'i18n' )->nt( $string1, $string2, $n, $textDomain );
		} else {
			trigger_error( 'nt() no i18n engine has currently been loaded', E_USER_NOTICE );
			return $string1;
		}
	}

	class I18n {

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
		 * Creates the correct i18n engine to use via Factory pattern. All i18n classes
		 * must extend the I18n_Base. If the i18n engine can not be constructed then it
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
			$engine = 'I18n_'.$engine;
			try {
				$tmpEngine = Registry::get( 'zula' )->loadLib( $engine, 'i18n' );
				if ( !($tmpEngine instanceof I18n_Base) ) {
					throw new I18n_InvalidEngine( 'i18n engine "'.$engine.'" does not extend I18n_Base, reverting to failsafe engine.' );
				}
			} catch ( I18n_InvalidEngine $e ) {
				Registry::get( 'log' )->message( $e->getMessage(), Log::L_WARNING );
				// Revert to the failsafe i18n engine
				$tmpEngine = Registry::get( 'zula' )->loadLib( 'I18n_Failsafe', 'i18n' );
			}
			return $tmpEngine;
		}

	}

?>
