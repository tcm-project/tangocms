<?php

/**
 * Zula Framework Dispatcher
 * --- Main functionaility is to take the router data and dispatch the request
 * by loading the correct controller.
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Dispatcher
 */

	class Dispatcher extends Zula_LibraryBase {

		/**
		 * Toggles if the dispatcher should display any of its own error
		 * messages (the 404 and 403 ones) if cntrlr fails to load
		 * @var bool
		 */
		protected $displayErrors = true;#

		/**
		 * When set to bool true, the correct HTTP status code will be set
		 * @var bool
		 */
		protected $setStatusHeader = false;

		/**
		 * Holds the dispatch status code
		 * @var int
		 */
		protected $statusCode = 200;

		/**
		 * Hold the requested controller object
		 * @var object
		 */
		protected $requestedCntrlr = null;

		/**
		 * Details used for the dispatch (module/controller/section etc)
		 * taken from the Router_Url instance
		 * @var array
		 */
		protected $dispatchData = null;

		/**
		 * Constructor
		 *
		 * @return object
		 */
		public function __construct() {
		}

		/**
		 * Toggles if the dispatcher should display its own 404/403 error
		 * messages if the requested cntrlr fails
		 *
		 * @param bool $errors
		 * @return object
		 */
		public function setDisplayErrors( $errors=true ) {
			$this->displayErrors = (bool) $errors;
			return $this;
		}

		/**
		 * Toggles if HTTP status code should be set
		 *
		 * @param bool $setHeaders
		 * @return object
		 */
		public function setStatusHeader( $setHeaders=true ) {
			$this->setStatusHeader = (bool) $setHeaders;
			return $this;
		}

		/**
		 * Gets the status code from the dispatcher
		 *
		 * @return int
		 */
		public function getStatusCode() {
			return (int) $this->statusCode;
		}

		/**
		 * Returns bool true if the dispatch has been made
		 *
		 * @return bool
		 */
		public function isDispatched() {
			return $this->requestedCntrlr instanceof Zula_ControllerBase;
		}

		/**
		 * Returns the Zula_ControllerBase instance of the requested cntrlr
		 *
		 * @return object|bool
		 */
		public function getReqCntrlr() {
			return $this->isDispatched() ? $this->requestedCntrlr : false;
		}

		/**
		 * Returns an array of details used for the dispached, for which
		 * module, cntrlr, section etc was used.
		 *
		 * @return array|bool
		 */
		public function getDispatchData() {
			return $this->isDispatched() ? $this->dispatchData : false;
		}

		/**
		 * Takes data from a Router_Url instance and attempts to load the correct cntrlr
		 * based upon that.
		 *
		 * @param Router_Url $request
		 * @param array $config
		 * @return string|bool
		 */
		public function dispatch( Router_Url $request, array $config=array() ) {
			$this->dispatchData = $request->asArray();
			$this->dispatchData['config'] = $config;
			unset( $config );
			while ( $preDispatch = Hooks::notify('cntrlr_pre_dispatch', $this->dispatchData) ) {
				if ( is_string($preDispatch) ) {
					return $preDispatch;
				} else if ( is_array($preDispatch) ) {
					$this->dispatchData = $preDispatch;
				}
			}
			try {
				$module = new Module( $this->dispatchData['module'] );
				$loadedCntrlr = $module->loadController( $this->dispatchData['controller'],
														 $this->dispatchData['section'],
														 $this->dispatchData['config'],
														 'SC' );
				$this->requestedCntrlr = $loadedCntrlr['cntrlr'];
				return $loadedCntrlr['output'];
			} catch ( Module_NoPermission $e ) {
				$this->statusCode = 403;
			} catch ( Module_ControllerNoExist $e ) {
				$this->statusCode = 404;
			} catch ( Module_NoExist $e ) {
				$this->statusCode = 404;
			}
			if ( $this->setStatusHeader ) {
				switch( $this->statusCode ) {
					case 200:
						header( 'HTTP/1.1 200 OK' );
						break;
					case 403:
						header( 'HTTP/1.1 403 Forbidden' );
						break;
					case 404:
						header( 'HTTP/1.1 404 Not Found' );
				}
			}
			if ( $this->displayErrors ) {
				// Display own custom error message in place of the modules output
				$view = new View( 'errors/'.$this->statusCode.'.html' );
				$view->assign( $this->dispatchData );
				$output = $view->getOutput();
				# hook event: cntrrl_error_output
				while( $tmpOutput = Hooks::notify( 'cntrlr_error_output', $this->statusCode, $output ) ) {
					if ( is_string( $tmpOutput ) ) {
						$output = $tmpOutput;
					}
				}
				return $output;
			} else {
				return false;
			}
		}

	}

?>
