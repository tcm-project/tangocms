<?php

/**
 * Zula Framework Dispatcher
 * --- Main functionaility is to take the router data and dispatch the request
 * by loading the correct controller.
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Dispatcher
 */

	class Dispatcher extends Zula_LibraryBase {

		/**
		 * Constants used for the request error codes
		 */
		const
				_403	= 403,
				_404	= 404;

		/**
		 * NPC (No Permission Controller) details to use instead of the
		 * requested details. These are used when the user does not have
		 * permission to the original request.
		 *
		 * @var array
		 */
		protected $npcData = array(
									'module'		=> 'session',
									'controller'	=> 'index',
									'section'		=> 'index',
									'config'		=> array('displayTitle' => true),
									);
		/**
		 * Toggles if the dispatcher should display any of its own error
		 * messages (the 404 and 403 ones)
		 * @var bool
		 */
		protected $displayErrors = true;

		/**
		 * Toggles whether to use a full page for error messages
		 * @var bool
		 */
		protected $fullPageErrors = false;

		/**
		 * Hold the requested controller object
		 * @var object
		 */
		protected $reqCntrl = null;

		/**
		 * Details used for the dispatch (module/controller/section etc)
		 * @var array
		 */
		protected $dispatchData = null;

		/**
		 * Holds if the request was a frontpage dispatch
		 * @var bool
		 */
		protected $fpDispatch = false;

		/**
		 * When set to bool true, this means the requested controller
		 * wants to be displayed by its self, no other content.
		 * @var bool
		 */
		protected $standalone = false;

		/**
		 * HTTP status code for the dispatchaer (200/401/404)
		 * @var int
		 */
		protected $httpStatus = 200;

		/**
		 * Constructor
		 *
		 * Sets some defaults such as the NPC (No Permission Controller/Module) and if
		 * to use full page errors
		 *
		 * @return object
		 */
		public function __construct() {
			try {
				$this->npcData['module'] = $this->_config->get( 'controller/npc' );
				if ( !trim( $this->npc ) ) {
					throw new Exception;
				}
			} catch ( Exception $e ) {
				$this->npcData['module'] = 'session';
			}
			$this->fpErrors = (bool) $this->_config->get( 'controller/full_page_errors' );
		}

		/**
		 * Sets whether the requested controller wants to be loaded
		 * as a standalone module
		 *
		 * @param bool $standalone
		 * @return object
		 */
		public function standalone( $standalone=true ) {
			$this->standalone = (bool) $standalone;
			return $this;
		}


		/**
		 * Toggles if the dispatcher should display its own 404/403 error
		 * messages, or just set the headers
		 *
		 * @param bool $errors
		 * @return object
		 */
		public function displayErrors( $errors=true ) {
			$this->displayErrors = (bool) $errors;
			return $this;
		}

		/**
		 * Returns the standalone value
		 *
		 * @return bool
		 */
		public function isStandalone() {
			return (bool) $this->standalone;
		}

		/**
		 * Returns if the requested dispatch was at the frontpage
		 *
		 * @return bool
		 */
		public function atFrontpage() {
			return (bool) $this->fpDispatch;
		}

		/**
		 * Checks if the dispatch has been made
		 *
		 * @return bool
		 */
		public function isDispatched() {
			return is_object( $this->reqCntrl );
		}

		/**
		 * Gets the status code from the dispatcher
		 *
		 * @return int
		 */
		public function getStatusCode() {
			return (int) $this->httpStatus;
		}

		/**
		 * Returns the objeect that is of the requested controller
		 *
		 * @return object|bool
		 */
		public function getReqCntrl() {
			if ( $this->isDispatched() ) {
				return $this->reqCntrl;
			} else {
				trigger_error( 'Dispatcher::getReqCntrl() requested controller does not exist, dispatch not yet made', E_USER_WARNING );
				return false;
			}
		}

		/**
		 * Returns the details used for the dispatch, such as which
		 * controller was loaded, and which section.
		 *
		 * @return array|bool
		 */
		public function getDispatchData() {
			if ( is_null( $this->dispatchData ) ) {
				trigger_error( 'Dispatcher::getDispatchData() dispatch has not yet been made, unable to get dispatch data', E_USER_WARNING );
				return false;
			} else {
				return $this->dispatchData;
			}
		}

		/**
		 * Takes router data from the loaded router and attempt to create a new module/controller
		 * based upon that. If certain data is missing then it will revert to the defaults for the
		 * site type, or if those are missing then the general defaults.
		 *
		 * If the user does not have permission to the ACL rule, and if we are not displaying
		 * full page errors and if we have a NPC (No Permission Controller) then it will
		 * change which controller
		 *
		 * @param bool $ajax	Is the request an AJAX request?
		 * @return string
		 */
		public function dispatch( $ajax=false ) {
			$routerData = $this->_router->getParsedPath()->asArray();
			$routerData['config'] = array();
			unset( $routerData['arguments'], $routerData['siteType'] );
			if ( !trim( $routerData['module'] ) ) {
				$this->_log->message( 'Dispatcher::dispatch() not enough router data, reverting to default map.', Log::L_DEBUG );
				/**
				 * Load the data from the correct layout map instead of using the provided
				 * URL data (as there is none). This means we are at the frontpage
				 */
				$this->fpDispatch = true;
				if ( _APP_MODE == 'installation' ) {
					$frontLayout = new Theme_layout( $this->_zula->getDir( 'install' ).'/zula-install-layout.xml' );
				} else {
					$frontLayout = new Theme_layout( $this->_router->getSiteType().'-default' );
				}
				$frontController = $frontLayout->getControllers( 'SC' );
				$frontController = array_shift( $frontController );
				$routerData = array(
									'module'	=> $frontController['mod'],
									'controller'=> $frontController['con'],
									'section'	=> $frontController['sec'],
									'config'	=> $frontController['config'],
									);
			}
			while ( $preDispatch = Hooks::notify('cntrlr_pre_dispatch', $routerData) ) {
				if ( is_string($preDispatch) ) {
					return $preDispatch;
				} else if ( is_array($preDispatch) ) {
					$routerData = $preDispatch;
				}
			}
			// Attempt to load the controller, changing it to the NPC controller if needed
			$loaded = false;
			do {
				try {
					$this->dispatchData = $routerData;
					$module = new Module( $routerData['module'] );
					try {
						$loadedCntrlr = $module->loadController( $routerData['controller'], $routerData['section'], $routerData['config'], 'SC' );
						$this->reqCntrl = $loadedCntrlr['cntrlr'];
						$loaded = true;
					 } catch ( Module_NoPermission $e ) {
						if ( $ajax ) {
							$this->_log->message( 'Dispatcher::dispatch() users does not have permission to module in AJAX request', Log::L_WARNING );
							return $this->error( $routerData, self::_403 );
						} else {
							if ( $routerData != $this->npcData && !$this->_session->isLoggedIn() && !$this->fullPageErrors ) {
								// Attempt to load the NPC controlelr that is set.
								$this->httpStatus = 403;
								$routerData = $this->npcData;
							} else {
								return $this->error( $routerData, self::_403 );
							}
						}
					} catch ( Module_ControllerNoExist $e ) {
						$this->_log->message( $e->getMessage(), Log::L_WARNING );
						return $this->error( $routerData, self::_404 );
					} catch ( Module_AjaxOnly $e ) {
						return $this->ajaxOnly( $routerData );
					}
				} catch ( Module_NoExist $e ) {
					return $this->error( $routerData, self::_404 );
				}
			} while( $loaded == false );
			// Return output from the requested controller
			if ( is_bool( $loadedCntrlr['output'] ) || trim( $loadedCntrlr['output'] ) ) {
				return $loadedCntrlr['output'];
			} else {
				return $ajax ? '' : '<p>'.t('Controller loaded but appears to display no content', I18n::_DTD).'</p>';
			}
		}

		/**
		 * Displays a message telling the user this contoller is AJAX Only
		 *
		 * @param array $routerData
		 * @return string
		 */
		public function ajaxOnly( $routerData ) {
			$routerData = array(
							'module'	=> $routerData['module'],
							'cntrlr'	=> trim($routerData['controller']) ? $routerData['controller'] : 'index',
							'section'	=> trim($routerData['section']) ? $routerData['section'] : 'index',
							);
			$this->_log->message( sprintf( 'controller "%s" must only be accessed by an AJAX request', implode('::', $routerData) ), Log::L_WARNING );
			return '<p>'.sprintf(t('Sorry, the requested controller "%s" can only be accessed by an AJAX request', I18n::_DTD), implode( '::', $routerData ) ).'</p>';
		}

		/**
		 * Displays either a simple error message or a full page error for
		 * the provided error code given. Used when user does not have
		 * permission to the requested controller, or it does not exist etc
		 *
		 * @param array $routerData
		 * @param int $errCode
		 * @return string
		 */
		protected function error( $routerData, $errCode=self::_404 ) {
			$routerData = array(
							'module'	=> $routerData['module'],
							'cntrlr'	=> trim($routerData['controller']) ? $routerData['controller'] : 'index',
							'section'	=> trim($routerData['section']) ? $routerData['section'] : 'index',
							);
			// Do the correct action with the error code provided
			switch( $errCode ) {
				case self::_403:
					$viewFile = $this->fullPageErrors ? 'errors/403_full_page.html' : 'errors/403.html';
					$header = 'HTTP/1.1 403 Forbidden';
					break;

				case self::_404:
				default:
					$viewFile = $this->fullPageErrors ? 'errors/404_full_page.html' : 'errors/404.html';
					$errCode = self::_404;
					$header = 'HTTP/1.1 404 Not Found';
			}
			$this->httpStatus = $errCode;
			if ( !headers_sent() ) {
				header( $header, true, $errCode );
			}
			if ( $this->displayErrors ) {
				// Continue to display the custom error pages
				if ( $this->isDispatched() ) {
					$this->reqCntrl->setTitle( t('Oops!', I18n::_DTD) );
				}
				$view = new View( $viewFile );
				$view->assign( $routerData );
				$output = $view->getOutput();
				# hook event: cntrlr_error_output
				while( $tmpOutput = Hooks::notify( 'cntrlr_error_output', $errCode, $output ) ) {
					if ( is_string( $tmpOutput ) ) {
						$output = $tmpOutput;
					}
				}
				if ( $this->fullPageErrors === true ) {
					die( $output );
				} else {
					return $output;
				}
			} else {
				return false;
			}
		}

	}

?>
