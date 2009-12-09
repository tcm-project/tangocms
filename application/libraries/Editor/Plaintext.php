<?php

/**
 * Zula Framework Editor
 * --- Plain Text
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Editor
 */

	class Editor_Plaintext extends Editor_Base {

		/**
		 * Pre-parse the text before, for example - inserting it
		 * into a databse. Things such as date/time should be parsed
		 * here.
		 *
		 * @return string
		 */
		public function preParse() {
			return $this->text;
		}

		/**
		 * Main method for parsing the text
		 *
		 * @param bool $break
		 * @return string
		 */
		public function parse( $break=false ) {
			$text = preg_replace_callback( '#([A-Z][A-Z0-9+.\-]+://|www\.)[A-Z0-9][A-Z0-9.\-]+\.[A-Z0-9.\-]+(:\d+)?(/([^\s\r\n]+)?)?#im',
											array($this, 'urlReplace'),
											zula_htmlspecialchars( $this->breakText($break) )
										 );
			return zula_nls2p( $text );
		}

		/**
		 * Replaces URLs in the text with a HTML anchor
		 *
		 * @param array $matches
		 * @return string
		 */
		protected function urlReplace( $matches ) {
			$url = strpos($matches[0], 'www.') === 0 ? 'http://'.$matches[0] : $matches[0];
			if ( empty( $this->options['nofollow'] ) ) {
				$format = '<a href="%1$s" class="external">%2$s</a>';
			} else {
				$format = '<a href="%1$s" class="external" rel="nofollow">%2$s</a>';
			}
			return sprintf( $format, $url, $matches[0] );
		}

	}

?>
