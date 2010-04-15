<?php

/**
 * Zula Framework Router
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Router
 */

	class Router extends Zula_LibraryBase {

		/**
		 * Constants used with Router::getRequestPath() to control trimming
		 */
		const
				_TRIM_DEFAULT	= 1,
				_TRIM_MAIN		= 2,
				_TRIM_ADMIN		= 3,
				_TRIM_ALL		= 4;

		/**
		 * Type of router that is to be used
		 * @var string
		 */
		protected $type = 'standard';

		/**
		 * Default scheme (HTTP/HTTPS) for the server
		 * @var string
		 */
		protected $defaultScheme = 'http';

		/**
		 * Site types to check against when attempting to find the current site type
		 * @var array
		 */
		protected $siteTypes = array('main', 'admin');

		/**
		 * Holds the current request path that is being used
		 * @var string
		 */
		protected $requestPath = '';

		/**
		 * Holds the Router_Url object of the request path value
		 * @var object
		 */
		protected $requestUrl = null;

		/**
		 * Constructor
		 * Gathers the current raw request path, and the protocol
		 * the server should be running (HTTP/HTTPS)
		 *
		 * @return object
		 */
		public function __construct() {
			if ( $this->_input->has( 'get', 'ns' ) || (function_exists( 'apache_get_modules' ) && !in_array('mod_rewrite', apache_get_modules())) ) {
				$this->type = 'standard';
			} else {
				$this->type = $this->_config->get( 'url_router/type' );
			}
			// Get the raw request path and the scheme of the server
			if ( $this->_input->has( 'get', 'url' ) ) {
				$this->requestPath = $this->_input->get( 'url' );
			}
			try {
				$this->defaultScheme = $this->_config->get( 'config/protocol' );
			} catch ( Config_KeyNoExist $e ) {
				$this->defaultScheme = $this->getScheme();
			}
		}

		/**
		 * Get the type of router that is in use
		 *
		 * @return string
		 */
		public function getType() {
			return $this->type;
		}

		/**
		 * Returns a Router_Url instance of the current parsed URL
		 * which includes the request path and query arguments.
		 *
		 * @return object
		 */
		public function getParsedUrl() {
			if ( !($this->requestUrl instanceof Router_Url) ) {
				// Parse the current raw request path and store it. Call the router_pre_parse hook first, though
				$this->requestPath = $this->getRawRequestPath();
				while( ($tmpUrl = Hooks::notify( 'router_pre_parse', trim($this->requestPath, '/'))) !== null ) {
					$this->requestPath = $tmpUrl;
				}
				$queryArgs = $this->_input->getAll( 'get' );
				unset( $queryArgs['url'] );
				$this->requestUrl = new Router_Url( $this->requestPath.'?'.http_build_query($queryArgs) );
			}
			return $this->requestUrl;
		}

		/**
		 * Checks if request path has a specified argument
		 *
		 * @param string $name
		 * @return bool
		 */
		public function hasArgument( $name ) {
			return $this->getParsedUrl()->hasArgument( $name );
		}

		/**
		 * Get a single argument from the parsed request path
		 *
		 * @param string $name
		 * @return string|bool
		 */
		public function getArgument( $name ) {
			return $this->getParsedUrl()->getArgument( $name );
		}

		/**
		 * Gets all arguments from the parsed request path
		 *
		 * @return array|bool
		 */
		public function getAllArguments() {
			return $this->getParsedUrl()->getAllArguments();
		}

		/**
		 * Returns the current request path
		 *
		 * @param int|bool $trim	Trim the site type off
		 * @return string
		 */
		public function getRequestPath( $trim=Router::_TRIM_DEFAULT ) {
			if ( is_int( $trim ) ) {
				switch( $trim ) {
					case Router::_TRIM_MAIN:
						$siteType = 'main';
						break;

					case Router::_TRIM_ADMIN:
						$siteType = 'admin';
						break;

					case Router::_TRIM_ALL:
						foreach( $this->getSiteTypes() as $tmpSiteType ) {
							if ( strpos( $this->requestPath, $tmpSiteType ) === 0 ) {
								$siteType = $tmpSiteType;
								break 2;
							}
						}
						break;

					case Router::_TRIM_DEFAULT:
					default:
						$siteType = $this->getDefaultSiteType();
				}
				if ( isset( $siteType ) && strpos( $this->requestPath, $siteType ) === 0 ) {
					return ltrim( substr( $this->requestPath, strlen($siteType) ), '/' );
				}
			}
			return $this->requestPath;
		}

		/**
		 * Returns the real/raw current request path that is in the address bar
		 *
		 * @return string
		 */
		public function getRawRequestPath() {
			try {
				return $this->_input->get( 'url' );
			} catch ( Input_KeyNoExist $e ) {
				return '';
			}
		}

		/**
		 * Returns the current URL, i.e. the one that is in the address bar, not
		 * after it has been processed.
		 *
		 * @return string
		 */
		public function getCurrentUrl() {
			return $this->getParsedUrl()->makeFull();
		}

		/**
		 * Constructs a new URL and returns the Router_Url object
		 *
		 * @param string $module
		 * @param string $controller
		 * @param string $section
		 * @param string $siteType
		 * @param array $arguments
		 * @return object
		 */
		public function makeUrl( $module, $controller=null, $section=null, $siteType=null, $arguments=array() ) {
			if ( $siteType == null ) {
				$siteType = $this->getSiteType();
			}
			if ( preg_match( '@(?:/|#|\?)@', $module ) == false ) {
				$url = new Router_Url;
				$url->siteType( $siteType )
					->module( $module )
					->controller( $controller )
					->section( $section )
					->arguments( $arguments );
			} else {
				$url = new Router_Url( $module ); # Module given was infact a request path, use that.
			}
			return $url;
		}

		/**
		 * Makes a full URL as a string (this will include the scheme for example)
		 *
		 * @param string $module
		 * @param string $controller
		 * @param string $section
		 * @param string $siteType
		 * @param array $arguments
		 * @param bool $useHttps
		 * @return string
		 */
		public function makeFullUrl( $module, $controller=null, $action=null, $siteType=null, $arguments=array(), $useHttps=null ) {
			return $this->makeUrl( $module, $controller, $action, $siteType, $arguments )
						->makeFull( '&amp;', null, $useHttps );
		}

		/**
		 * Checks if a site type exists
		 *
		 * @param string $siteType
		 * @return bool
		 */
		public function siteTypeExists( $siteType ) {
			return in_array( $siteType, $this->siteTypes );
		}

		/**
		 * Returns every site there there is
		 *
		 * @return array
		 */
		public function getSiteTypes() {
			return $this->siteTypes;
		}

		/**
		 * Adds a new site type to look for when parsing the URL
		 *
		 * @param string $siteType
		 * @return bool
		 */
		public function addSiteType( $siteType ) {
			if ( isset( $this->siteTypes[ $siteType ] ) ) {
				return false;
			} else {
				$this->siteType[] = $siteType;
				return true;
			}
		}

		/**
		 * Gets the current site type that has been set, if not
		 * then it will revert to the default site type.
		 *
		 * @return string
		 */
		public function getSiteType() {
			return $this->getParsedUrl()->siteType;
		}

		/**
		 * Gets the deafult site type to use when no site type found
		 *
		 * @return string
		 */
		public function getDefaultSiteType() {
			return 'main';
		}

		/**
		 * Returns the current base URL (protocol, domain, base path)
		 *
		 * @return string
		 */
		public function getBaseUrl() {
			$host = $_SERVER['HTTP_HOST'];
			if ( strpos( $host, 'http://' ) !== 0 && strpos( $host, 'https://' ) !== 0 ) {
				$host = $this->getDefaultScheme().'://'.$host;
			}
			return rtrim( $host, '/' )._BASE_DIR;
		}

		/**
		 * Gets the actual scheme that is currently being used
		 *
		 * @return string
		 */
		public function getScheme() {
			return (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') ? 'http' : 'https';
		}

		/**
		 * Gets the default scheme for the server
		 *
		 * @return string
		 */
		public function getDefaultScheme() {
			return $this->defaultScheme;
		}

	}

?>
