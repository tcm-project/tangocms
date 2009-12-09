<?php

/**
 * Zula Framework Input
 * Cleans all input data (POST, GET etc) and then gives the superglobals
 * a blank array value.
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Input
 */

	class Input extends Zula_LibraryBase {

		/**
		 * The CSRF input element name used
		 */
		const _CSRF_INAME = 'zula-csrf-token';

		/**
		 * Holds which input data should be allowed
		 * IE, you can block Post, Get and Cooies
		 * @var array
		 */
		protected $allow = array(
								'POST'		=> true,
								'GET'		=> true,
								'COOKIE'	=> false,
								);

		/**
		 * Every input we are using - pretty much like $_REQUEST
		 * @var array
		 */
		private $inputs = array(
								'POST'		=> null,
								'GET'		=> null,
								'COOKIE'	=> null,
								);

		/**
		 * Returns the CSRF error message that should be used
		 *
		 * @return string
		 */
		static public function csrfMsg() {
			return t('Suspected malicious CSRF attack, unable to proceed', Locale::_DTD);
		}

		/**
		 * Constructor function
		 * Sets the class properties that will be used later on in the class.
		 *
		 * @return object
		 */
		public function __construct() {
			$inputConf = $this->_config->get( 'input' );
			$this->allow = array(
								'POST'		=> (bool) $inputConf['allow_post'],
								'GET'		=> (bool) $inputConf['allow_get'],
								'COOKIE'	=> (bool) $inputConf['allow_cookies'],
								);
			if ( function_exists( 'set_magic_quotes_runtime' ) ) {
				// Yes yes I know it is deprecated, that is why we shut it up.
				@set_magic_quotes_runtime( false );
			}
		}

		/**
		 * Makes sure keys only contain certain characters
		 *
		 * @param string $key
		 * @return string
		 */
		protected function cleanInputKey( $key ) {
			return str_replace( "\0", '', $key );
		}

		/**
		 * Cleans input data by removing slashes if needed, standadizes new lines
		 * and also helps protect against XSS attacks.
		 *
		 * @param mixed $data
		 * @return mixed
		 */
		protected function cleanInputData( $data ) {
			static $hasMQ = null;
			if ( is_array( $data ) ) {
				foreach( $data as $key=>$val ) {
					$data[ $this->cleanInputKey( $key ) ] = $this->cleanInputData( $val );
				}
				return $data;
			} else if ( $hasMQ || function_exists( 'get_magic_quotes_gpc' ) && get_magic_quotes_gpc() ) {
				$data = stripslashes( $data );
				$hasMQ = true;
			} else {
				$hasMQ = false;
			}
			// Standadize newlines
			return preg_replace( "/\015\012|\015|\012/", "\n", str_replace("\0", '', $data) );
		}

		/**
		 * Generates a unique token key, to help protect
		 * against CSRF attacks
		 *
		 * @param bool $withHtml 	Can return the HTML input to be used
		 * @return string
		 */
		public function createToken( $withHtml=false ) {
			do {
				$token = zula_hash( uniqid( session_id(), true ) );
			} while( isset( $_SESSION['csrf-tokens'][ $token ] ) );
			// Store the token and life of it
			$_SESSION['csrf-tokens'][ $token ] = time();
			if ( $withHtml == false ) {
				return $token;
			} else {
				return '<div><input type="hidden" name="'.self::_CSRF_INAME.'" value="'.$token.'"></div>';
			}
		}

		/**
		 * Checks if a CSRF Token exists in session and is
		 * in-date, as a token will only exists for 2hours
		 *
		 * @param string $from	Either 'post' or 'get'
		 * @return bool
		 */
		public function checkToken( $from='post' ) {
			try {
				$token = $from == 'post' ? $this->post( self::_CSRF_INAME ) : $this->get( 'zct' ); # zct = Zula CSRF Token
			} catch ( Input_KeyNoExist $e ) {
				return false;
			}
			unset( $this->inputs['POST'][ self::_CSRF_INAME ] );
			if ( !empty( $_SESSION['csrf-tokens'][ $token ] ) ) {
				if ( time() <= ($_SESSION['csrf-tokens'][ $token ] + (60*60*2)) ) {
					return true;
				}
				unset( $_SESSION['csrf-tokens'][ $token ] ); # Remove and invalidate the token
			}
			// Log the possible CSRF attack
			$this->_log->message( '***CSRF token failed*** Possible malicious attack!', Log::L_WARNING );
			return false;
		}

		/**
		 * Fetchs input from the correct superglobal and empties
		 * the array so they can not be used directly in the script.
		 *
		 * @param string $type
		 * @return bool
		 */
		protected function fetchInput( $type ) {
			if ( $this->allow[ $type ] === true ) {
				if ( $this->inputs[ $type ] === null ) {
					switch( $type ) {
						case 'POST':
							$inputData = $_POST;
							if ( isset( $_SESSION['post-restore'] ) ) {
								$inputData = zula_merge_recursive( $inputData, $_SESSION['post-restore'] );
								unset( $_SESSION['post-restore'] );
							}
							$_POST = array();
							break;

						case 'GET':
							$inputData = $_GET;
							$_GET = array();
							break;

						case 'COOKIE':
							$inputData = $_COOKIE;
							$_COOKIE = array();
							break;
					}
					foreach( $inputData as $key=>$val ) {
						if ( ($newKey = $this->cleanInputKey($key)) != $key ) {
							unset( $inputData[ $key ] );
						}
						$inputData[ $newKey ] = $this->cleanInputData( $val );
					}
					$this->inputs[ $type ] = $inputData;
				}
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Checks if a input key exists for the correct type
		 *
		 * @param string $type
		 * @param string $key
		 * @return bool
		 */
		public function has( $type, $key ) {
			try {
				$this->get( $key, $type, false );
			} catch ( Input_KeyNoExist $e ) {
				return false;
			}
			return true;
		}

		/**
		 * Returns the entire array of the correct input type
		 *
		 * @param string $type
		 * @param bool $trim
		 * @return array|bool
		 */
		public function getAll( $type, $trim=true ) {
			$type = strtoupper( $type );
			if ( $this->inputs[ $type ] === null ) {
				$this->fetchInput( $type );
			}
			if ( isset( $this->inputs[ $type ] ) ) {
				$tmpVal = $this->inputs[ $type ];
				if ( $trim ) {
					$tmpVal = is_array($tmpVal) ? zula_array_map_recursive( 'trim', $tmpVal ) : trim( $tmpVal );
				}
				return $tmpVal;
			} else {
				trigger_error( 'Input::getAll() could not get all of type "'.$type.'". Type is invalid', E_USER_WARNING );
				return false;
			}
		}

		/**
		 * Returns the value of the correct input type. To access a
		 * multidimensional array, use the syntax 'foo/bar/car' where
		 * a '/' deliminates the next array index.
		 *
		 * @param string $key
		 * @param string $type
		 * @param bool $trim
		 * @return mixed
		 */
		public function get( $key, $type='get', $trim=true ) {
			$type = strtoupper( $type );
			if ( $this->inputs[ $type ] === null ) {
				$this->fetchInput( $type );
			}
		 	$key = preg_split( '#(?<!\\\)/#', trim($key, '/') );
			foreach( $key as $val ) {
				$val = stripslashes( $val );
				if ( !isset( $tmpVal ) && isset( $this->inputs[ $type ][ $val ] ) ) {
					$tmpVal = $this->inputs[ $type ][ $val ];
				} else if ( isset( $tmpVal[ $val ] ) ) {
					$tmpVal = $tmpVal[ $val ];
				} else {
					throw new Input_KeyNoExist( 'input key "'.stripslashes( implode('/', $key) ).'" for data type "'.$type.'" does not exist' );
				}
			}
			if ( $trim ) {
				$tmpVal = is_array($tmpVal) ? zula_array_map_recursive( 'trim', $tmpVal ) : trim( $tmpVal );
			}
			return $tmpVal;
		}

		/**
		 * Quick alias function to get( 'post ... )
		 *
		 * @param string $key
		 * @param bool $trim
		 * @return string
		 * @see Input::get
		 */
		public function post( $key, $trim=true ) {
			return $this->get( $key, 'post', $trim );
		}

		/**
		 * Another quick alias to get( 'cookie' ... )
		 *
		 * @param string $key
		 * @param bool $trim
		 * @return string
		 * @see Input::get
		 */
		public function cookie( $key, $trim=true ) {
			return $this->get( $key, 'cookie', $trim );
		}

		/**
		 * Checks if an array of items exists for either of
		 * the methods/inputs (GET, POST, COOKIE)
		 *
		 * @param array $items
		 * @param string $type
		 * @return bool
		 */
		public function hasItems( array $items, $type='post' ) {
			foreach( $items as $item ) {
				if ( !$this->has( $type, $item ) ) {
					return false;
				}
			}
			return true;
		}

	}

?>
