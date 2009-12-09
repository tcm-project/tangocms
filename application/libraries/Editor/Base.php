<?php
// $Id: Base.php 2768 2009-11-13 18:12:34Z alexc $

/**
 * Zula Framework Editor
 * --- Base class that all parser will extend
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Editor
 */

	abstract class Editor_base extends Zula_LibraryBase {

		/**
		 * Options to use in the parser
		 * @var array
		 */
		protected $options = array();

		/**
		 * Text that will be parsed
		 * @var string
		 */
		public $text = '';

		/**
		 * Constructor
		 * Sets text to use for formatting
		 *
		 * @param string $text
		 * @param array $options
		 * @return object
		 */
		public function __construct( $text, array $options=array() ) {
			$this->text = trim( $text );
			$this->options = $options;
		}

		/**
		 * Pre-parse the text before, for example - inserting it
		 * into a databse. Things such as date/time should be parsed
		 * here.
		 *
		 * @return string
		 */
		abstract public function preParse();

		/**
		 * Main method for parsing the text
		 *
		 * @return string
		 */
		abstract public function parse();

		/**
		 * Returns the text either split by the '<!--break-->' point,
		 * or with the break point removed.
		 *
		 * @param bool $break
		 * @return string
		 */
		protected function breakText( $break=false ) {
			if ( $break ) {
				$split = preg_split( '#(?<!\\\)<!--break-->#', $this->text );
				$text = $split[0];
			} else {
				$text = preg_replace( '#(?<!\\\)<!--break-->#', '', $this->text );
			}
			return trim( str_replace( '\<!--break-->', '<!--break-->', $text ) );
		}

		/**
		 * Updates/adds in parser options that will be used when parsing
		 *
		 * @param array $options
		 * @return bool
		 */
		public function setOptions( array $options ) {
			$this->options = $options;
			return true;
		}

	}

?>
