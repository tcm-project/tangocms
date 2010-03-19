<?php

/**
 * Zula Framework Library
 * --- Handles creation of URLs within the framework
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Router
 */

	class Router_url extends Zula_LibraryBase {

		/**
		 * Details of the URL after it was parsed
		 * @var array
		 */
		protected $parsed = array(
								'siteType'		=> 'main',
								'module'		=> null,
								'controller'	=> null,
								'section'		=> null,
								'arguments'		=> array(),
								);

		/**
		 * URL query string arguments to use
		 * @var array
		 */
		protected $queryStringArgs = array();

		/**
		 * URL fragment
		 * @var string
		 */
		protected $fragment = null;

		/**
		 * Constructor
		 * Can load a given request path and parses it into the
		 * parts. Query arguments and a fragment can be provided.
		 *
		 * @param string $requestPath
		 * @return object
		 */
		public function __construct( $requestPath=null ) {
			if ( $requestPath = trim($requestPath, '/') ) {
				// Get any query arguments or fragments from the URL
				$this->fragment = parse_url( $requestPath, PHP_URL_FRAGMENT );
				if ( $queryParsed = parse_url($requestPath, PHP_URL_QUERY) ) {
					parse_str( $queryParsed, $this->queryStringArgs );
				}
				/**
				 * Begin the actual parsing of the URL to find out what data is given
				 */
				$splitPath = explode( '/', parse_url($requestPath, PHP_URL_PATH) );
				// Check for a provided site type
				if ( in_array( $splitPath[0], $this->_router->getSiteTypes() ) ) {
					$this->parsed['siteType'] = $splitPath[0];
					array_shift( $splitPath );
				}
				$splitCount = count( $splitPath );
				switch( true ) {
					case $splitCount >= 4:
						// Parse the URL Arguments provided
						$arguments = array_slice( $splitPath, 3 );
						$argLen = $splitCount - 4;
						for( $i = 0; $i <= $argLen; $i = $i+2 ) {
							$key = urldecode( $arguments[ $i ] );
							$val = isset($arguments[ $i+1 ]) ? urldecode( $arguments[ $i+1 ] ) : null;
							$this->parsed['arguments'][ $key ] = $val;
						}

					case $splitCount == 3:
						$this->parsed['section'] = $splitPath[2];

					case $splitCount == 2:
						$this->parsed['controller'] = $splitPath[1];

					case $splitCount == 1:
						$this->parsed['module'] = $splitPath[0];
				}
			}
		}

		/**
		 * Allows quick acccess to the parsed request path details
		 *
		 * @param string $name
		 * @return mixed
		 */
		public function __get( $name ) {
			return isset($this->parsed[ $name ]) ? $this->parsed[ $name ] : parent::__get( $name );
		}

		/**
		 * Alias to Router_Url::make()
		 *
		 * @return string
		 */
		public function __toString() {
			return $this->make();
		}

		/**
		 * Gets all of the parsed details as an array. This will only be useful when
		 * the constructor was given a URL to parse.
		 *
		 * @return array
		 */
		public function asArray() {
			return $this->parsed;
		}

		/**
		 * Checks if an argument exist
		 *
		 * @param string $name
		 * @return bool
		 */
		public function hasArgument( $name ) {
			return isset( $this->parsed['arguments'][ $name ] );
		}

		/**
		 * Gets a single argument, if it exists
		 *
		 * @param string $name
		 * @return string
		 */
		public function getArgument( $name ) {
			if ( isset( $this->parsed['arguments'][ $name ] ) ) {
				return $this->parsed['arguments'][ $name ];
			} else {
				throw new Router_ArgNoExist( $name );
			}
		}

		/**
		 * Gets all of the arguments for the URL
		 *
		 * @return array
		 */
		public function getAllArguments() {
			return (array) $this->parsed['arguments'];
		}

		/**
		 * Checks if a query argument exists
		 *
		 * @param string $name
		 * @return bool
		 */
		public function hasQueryArg( $name ) {
			return isset( $this->queryStringArgs[ $name ] );
		}

		/**
		 * Gets a single query argument
		 *
		 * @param string $name
		 * @return string
		 */
		public function getQueryArg( $name ) {
			if ( isset( $this->queryStringArgs[ $name ] ) ) {
				return $this->queryStringArgs[ $name ];
			} else {
				throw new Router_ArgNoExist( $name );
			}
		}

		/**
		 * Gets all of the query arguments
		 *
		 * @return array
		 */
		public function getAllQueryArgs() {
			return (array) $this->queryStringArgs;
		}

		/**
		 * Removes 1 or all request path arguments
		 *
		 * @param string $arg,...
		 * @return object
		 */
		public function removeArguments( $arg=null ) {
			if ( func_num_args() > 0 ) {
				foreach( func_get_args() as $arg ) {
					unset( $this->parsed['arguments'][ $arg ] );
				}
			} else {
				$this->parsed['arguments'] = array();
			}
			return $this;
		}

		/**
		 * Removes 1 or all url query arguments
		 *
		 * @param string $arg,...
		 * @return object
		 */
		public function removeQueryArgs( $arg=null ) {
			if ( func_num_args() > 0 ) {
				foreach( func_get_args() as $arg ) {
					unset( $this->queryStringArgs[ $arg ] );
				}
			} else {
				$this->queryStringArgs = array();
			}
			return $this;
		}

		/**
		 * Sets Site Type
		 *
		 * @param string $siteType
		 * @return object
		 */
		public function siteType( $siteType ) {
			$this->parsed['siteType'] = (string) $siteType;
			return $this;
		}

		/**
		 * Sets Module
		 *
		 * @param string $module
		 * @return object
		 */
		public function module( $module ) {
			$this->parsed['module'] = (string) $module;
			return $this;
		}

		/**
		 * Sets Controller
		 *
		 * @param string $controller
		 * @return object
		 */
		public function controller( $controller ) {
			$this->parsed['controller'] = (string) $controller;
			return $this;
		}

		/**
		 * Sets Section
		 *
		 * @param string $section
		 * @return object
		 */
		public function section( $section ) {
			$this->parsed['section'] = (string) $section;
			return $this;
		}

		/**
		 * Sets the URL arguments to use
		 *
		 * @param array $arguments
		 * @param bool $overwrite
		 * @return object
		 */
		public function arguments( array $arguments, $overwrite=false ) {
			if ( $overwrite ) {
				$this->parsed['arguments'] = $arguments;
			} else {
				$this->parsed['arguments'] = array_merge( $this->parsed['arguments'], $arguments );
			}
			return $this;
		}

		/**
		 * Sets URL query string arguments to use
		 *
		 * @param array $queryArgs
		 * @param bool $overwrite
		 * @return object
		 */
		public function queryArgs( array $queryArgs, $overwrite=false ) {
			if ( $overwrite ) {
				$this->queryStringArgs = $queryArgs;
			} else {
				$this->queryStringArgs = array_merge( $this->queryStringArgs, $queryArgs );
			}
			return $this;
		}

		/**
		 * Sets the URL fragment
		 *
		 * @param string $fragment
		 * @return object
		 */
		public function fragment( $fragment ) {
			$this->fragment = (string) $fragment;
			return $this;
		}

		/**
		 * Builds up a string request path to be used
		 *
		 * @param string $separator
		 * @param string $type
		 * @return string
		 */
		public function make( $separator='&amp;', $type=null ) {
			$data = array();
			foreach( $this->parsed as $key=>$val ) {
				if ( $key == 'arguments' || $key == 'siteType' && $val == $this->_router->getDefaultSiteType() ) {
					continue;
				} else if ( $key != 'siteType' && $val == null ) {
					$val = 'index';
				}
				$data[ $key ] = $val;
			}
			$arguments = $this->parsed['arguments'];
			$argString = '';
			if ( empty( $arguments ) ) {
				// No need to keep empty parts on
				while( end($data) == 'index' ) {
					array_pop( $data );
				}
			} else {
				foreach( $arguments as $key=>$val ) {
					$argString .= '/'.$key.'/'.$val;
				}
			}
			$requestPath = trim( implode('/', $data), '/' ).$argString;
			# hook event: router_make_url
			$tmpRp = Hooks::notifyAll( 'router_make_url', $requestPath );
			if ( is_array( $tmpRp ) ) {
				$requestPath = trim( end($tmpRp), '/' );
			}
			// Create the correct URL based upon router type
			if ( !$type ) {
				$type = $this->_router->getType();
			}
			unset( $this->queryStringArgs['url'] );
			if ( $type == 'standard' ) {
				// Add in the 'url' query string needed, force it to be first index
				if ( $requestPath ) {
					$this->queryStringArgs = array_merge( array('url' => $requestPath),
														  $this->queryStringArgs
														 );
				}
				if ( $this->_input->has( 'get', 'ns' ) ) {
					$this->queryStringArgs['ns'] = '';
				}
				$url = _BASE_DIR.'index.php';
			} else {
				$url = _BASE_DIR.$requestPath;
			}
			if ( !empty( $this->queryStringArgs ) ) {
				$url .= '?'.str_replace( '%2F', '/', http_build_query($this->queryStringArgs, null, $separator) );
			}
			return $this->fragment ? $url.'#'.$this->fragment : $url;
		}

		/**
		 * Makes a full URL instead of just a relative one. This means it will
		 * include the http:// or what ever is needed to create a full/external link
		 *
		 * @param string $separator
		 * @param string $type
		 * @param bool $useHttps	Force use of HTTPS protocol
		 * @return string
		 */
		public function makeFull( $separator='&amp;', $type=null, $useHttps=null ) {
			$host = $_SERVER['HTTP_HOST'];
			if ( is_bool( $useHttps ) ) {
				$host = preg_replace( '#^(https?://)?#i', ($useHttps ? 'https://' : 'http://'), $host );
			} else {
				$host = zula_url_add_scheme( $host, $this->_router->getDefaultScheme() );
			}
			return trim( $host, '/' ).$this->make( $separator, $type );
		}

	}

?>
