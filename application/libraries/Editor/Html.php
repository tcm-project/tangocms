<?php
// $Id: Html.php 2768 2009-11-13 18:12:34Z alexc $

/**
 * Zula Framework Editor
 * --- HTML Parser
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Editor
 */

	class Editor_html extends Editor_Base {

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
			return $this->breakText( $break );
		}

	}

?>
