<?php

/**
 * Zula Framework Editor
 * --- Allows for text to be formatted using different type of editors
 * such as MediaWiki, HTML, BB
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Editor
 */

	class Editor extends Zula_LibraryBase {

		/**
		 * Type of formatting the text will use
		 * @var string
		 */
		protected $formatting = 'html';

		/**
		 * Parsed options/config to pass into constructor
		 * @var array
		 */

		protected $parserOptions = array();

		/**
		 * Correct parser to parse the text
		 * @var string
		 */
		protected $parser;

		/**
		 * All of the editor libs that have been used
		 * @var array
		 */
		static private $usedEditors = array();

		/**
		 * Constructor
		 * Optionally set the text that will be used as well as
		 * the formatting.
		 *
		 * @param string $text
		 * @param string $format
		 * @return object
		 */
		public function __construct( $text=null, array $options=array() ) {
			$this->parserOptions = $options;
			$this->formatting = $this->load( $text );
		}

		/**
		 * Allow access to some protected/private properties
		 * for easier interface
		 *
		 * @param string $name
		 * @return mixed
		 */
		public function __get( $name ) {
			switch( $name ) {
				case 'formatting':
					return $this->formatting;

				default:
					return parent::__get( $name );
			}
		}

		/**
		 * Gets all available Editor Sets that can be used
		 *
		 * @return array
		 */
		static public function availableFormats() {
			$formats = array();
			foreach( glob( Registry::get( 'zula' )->getDir( 'libs' ).'/Editor/*.php') as $file ) {
				$editor = pathinfo( $file, PATHINFO_FILENAME );
				if ( $editor != 'Base' ) {
					$formats[] = strtolower( $editor );
				}
			}
			return $formats;
		}

		/**
		 * Default Format Set
		 *
		 * @return string
		 */
		static public function defaultFormat() {
			try {
				return strtolower( Registry::get( 'config' )->get( 'editor/default' ) );
			} catch( Config_KeyNoExist $e ) {
				return 'html';
			}
		}

		/**
		 * Returns which editor libs have been used to parse text
		 *
		 * @return array
		 */
		static public function usedEditors() {
			return array_unique( self::$usedEditors );
		}

		/**
		 * Updates/adds in parser options that will be used when parsing
		 *
		 * @param array $options
		 * @return bool
		 */
		public function setOptions( array $options ) {
			return $this->parser->setOptions( $options );
		}

		/**
		 * Store the text that is to be parsed later on, also try
		 * to parse the shebang to find out if any format set has
		 * been specified.
		 *
		 * @param string $text
		 * @return string	Formatting that will be used
		 */
		protected function load( $text ) {
			$format = $this->defaultFormat();
			if ( preg_match( '/^#\!([A-Z0-9_\-]+)/i', $text, $matches ) ) {
				$tmpFormat = trim( $matches[1] );
				if ( $tmpFormat == 'plain_text' ) {
					$tmpFormat = 'plaintext';
				}
				if ( in_array( $tmpFormat, $this->availableFormats() ) ) {
					$format = $tmpFormat;
				}
				$text = preg_replace( '/^#\!([A-Z0-9_\-]+)/i', '', $text, 1 );
			}
			$class = 'Editor_'.$format;
			$this->parser = new $class( $text, $this->parserOptions );
			// Store which editors have been used to parse text
			self::$usedEditors[] = $format;
			return $format;
		}

		/**
		 * Uses the parser to pre-parse the text before it
		 * should be stored.
		 *
		 * @return string
		 */
		public function preParse() {
			while( $tmpText = Hooks::notify( 'editor_pre_parse', $this->parser->text ) ) {
				if ( is_string( $tmpText ) ) {
					$this->parser->text = $tmpText;
				}
			}
			$this->parser->text = $this->parser->preParse();
			return '#!'.$this->formatting."\n".$this->parser->text;
		}

		/**
		 * Parses text using correct parser, if $break is set
		 * to a value which equates to true, the text will be
		 * split at the '<!--break-->' point, instead of
		 * parsing the entire document
		 *
		 * @param bool $break
		 * @param bool $disablePhp
		 * @return string
		 */
		public function parse( $break=false, $disablePhp=false ) {
			if ( $disablePhp == false && $this->_config->get( 'editor/parse_php' ) ) {
				// Eww =\ 10 kittens just died because of this.
				ob_start();
				echo eval( '?>'.$this->parser->text );
				$this->parser->text = ob_get_clean();
			}
			$parsed = $this->parser->parse( $break );
			while( $tmpText = Hooks::notify( 'editor_parse', $parsed ) ) {
				if ( is_string( $tmpText ) ) {
					$parsed = $tmpText;
				}
			}
			// Fix for bug #275, replace #foobar with {URL}#foobar (needed due to <base>)
			$parsed = preg_replace( '/\shref="#([^"]*?)"/i', ' href="' . $this->_router->getRequestPath() . '#$1"', $parsed );
			if ( $this->_router->getType() == 'standard' ) {
				// Fix for Bug #167, rewrite URLs to include 'index.php?url='
				$parsed = preg_replace_callback( '#\shref="(?!(/|[A-Z][A-Z0-9+.\-]+://))(.*?)"#i', array($this, 'cbFixSefUrl'), $parsed );
			}
			return $parsed;
		}
		
		/**
		 * Callback to convert given SEF urls to standard, stops URLs breaking
		 * which fixes Bug #167
		 *
		 * @param array $matches
		 * @return string
		 */
		protected function cbFixSefUrl( $matches ) {
			if ( ($qPos = strpos($matches[2], '?')) !== false ) {
				$url = substr($matches[2], 0, $qPos).'&'.substr($matches[2], $qPos+1);
			} else {
				$url = $matches[2];
			}
			return ' href="index.php?url='.$url.'"';
		}

	}

?>
