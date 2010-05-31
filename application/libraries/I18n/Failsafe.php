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
		 * @param string $textDomain
		 * @return string
		 */
		public function nt( $string1, $string2, $n, $textDomain=null ) {
			return $n == 1 ? $string1 : $string2;
		}

	}

?>