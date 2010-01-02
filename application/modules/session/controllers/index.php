<?php

/**
 * Zula Framework Module (Session)
 * Allows the user to login/logout as well as show a simple profile
 *
 * Within Zula/TangoCMS a user never really 'logs in' like a 'normal' application
 * but instead it's more of a 'change user', where the user changes from being logged in
 * as guest, to which ever credentials they provided (and were correct).
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Session
 */

	class Session_controller_index extends Zula_ControllerBase {

		/**
		 * Login Method to be used
		 * @var string (username|email)
		 */
		protected $loginMethod;

		/**
		 * Maximum allowed login attempts
		 * @var int
		 */
		protected $maxLoginAttempts = 5;

		/**
		 * Constructor
		 *
		 * @return object
		 */
		public function __construct( $moduleDetails, $config, $sector ) {
			parent::__construct( $moduleDetails, $config, $sector );
			try {
				$this->loginMethod = $this->_config->get( 'session/login_by' );
				if ( !trim( $this->loginMethod ) ) {
					throw new Exception;
				}
			} catch ( Exception $e ) {
				$this->loginMethod = 'username';
			}
			$this->maxLoginAttempts = abs( $this->_config->get( 'session/max_login_attempts' ) );
		}

		/**
		 * Shows the login form so that a user can login, also shows
		 * links such as 'Register' and 'Forgot Password?'
		 *
		 * If the user is already logged in then it will show a simple profile
		 *
		 * @return string
		 */
		public function indexSection() {
			$rcd = $this->_dispatcher->getDispatchData();
			if ( !$this->inSector( 'SC' ) && $rcd['module'] == 'session' && $rcd['controller'] == 'index' && $rcd['section'] == 'index' ) {
				// The session module is already loaded into another sector (not 'SC'), do not display it
				return false;
			}
			$this->_locale->textDomain( $this->textDomain() );
			if ( $this->_session->isLoggedIn() ) {
				$this->setTitle( t('Simple Profile') );
				// Display a very simple profile
				$view = $this->loadView( 'index/simple_profile.html' );
				$view->assign( array('USER' => $this->_session->getUser()) );
				return $view->getOutput();
			} else if ( $this->maxLoginAttempts > 0 && $this->_model()->getLoginAttempts() >= $this->maxLoginAttempts ) {
				// Show view form saying they are blocked for 10 mins
				$this->setTitle( t('Login') );
				$view = $this->loadView( 'index/blocked.html' );
				$view->assign( array('MAX_ATTEMPTS' => $this->maxLoginAttempts) );
				return $view->getOutput();
			} else {
				if ( $this->inSector( 'SC' ) ) {
					$_SESSION['post-restore'] = $this->_input->getAll( 'post' );
					if ( !headers_sent() ) {
						header( 'HTTP/1.1 403 Forbidden', true, 403 );
					}
				}
				$this->setTitle( t('Login') );
				/**
				 * Display form for logging in, and save any post input data so it can
				 * be restored on the next page request.
				 *
				 * Also make the correct URLs to use, incase Force HTTPS is enabled
				 */
				if ( $this->_config->get( 'session/force_https' ) ) {
					if ( $this->inSector( 'SC' ) && $this->_router->getScheme() != 'https' ) {
						return zula_redirect( $this->_router->makeUrl( 'session' )->makeFull('&', null, true) );
					}
					$formUrl = $this->_router->makeFullUrl( 'session', 'index', 'login', null, array(), true );
					$registerUrl = $this->_router->makeFullUrl( 'session', 'register', null, null, array(), true );
				} else {
					$formUrl = $this->_router->makeUrl( 'session', 'index', 'login' );
					$registerUrl = $this->_router->makeUrl( 'session', 'register' );
				}
				$view = $this->loadView( 'index/login.html' );
				$view->assign( array(
									'FORM_URL' 		=> $formUrl,
									'FORGOT_URL' 	=> $this->_router->makeUrl( 'session', 'reset' ),
									'REGISTER_URL'	=> $registerUrl,
									'LOGIN_BY'		=> $this->loginMethod,
									));
				return $view->getOutput();
			}
		}

		/**
		 * Attempts to log a user in with the credentials provided by the form.
		 *
		 * @return string
		 */
		public function loginSection() {
			$this->_locale->textDomain( $this->textDomain() );
			$this->_session->storePrevious( false );
			$loggedIn = false;
			if ( $this->maxLoginAttempts > 0 && $this->_model()->getLoginAttempts() >= $this->maxLoginAttempts ) {
				$this->_event->error( t('Maximum number of login attempts reached') );
			} else if ( $this->_input->has( 'post', 'session/identifier' ) ) {
				$_SESSION['post-restore'] = $this->_input->getAll( 'post' );
				unset( $_SESSION['post-restore']['session'] );
				// Validate the provided value
				$identifier = $this->_input->post( 'session/identifier', false );
				$validator = $this->loginMethod == 'username' ? new Validator_Length( 0, 24 ) : new Validator_Email;
				if ( ($result = $validator->validate($identifier)) === true ) {
					try {
						$uid = $this->_model()->checkCredentials( $identifier,
																  $this->_input->post('session/password', false),
																  $this->loginMethod
																);
						$rememberMe = $this->_input->has( 'post', 'session/remember' );
						if ( $this->_session->switchUser( $uid, $rememberMe ) === false ) {
							$this->_event->error( t('Sorry, this user account is currently locked') );
						} else {
							$loggedIn = true;
						}
					} catch ( Session_UserNotActivated $e ) {
						$this->_event->error( t('This user account has not yet been activated') );
					} catch ( Session_InvalidCredentials $e ) {
						$this->_event->error( t('Username and/or password provided are incorrect') );
					}
				} else {
					$this->_event->error( sprintf( $result, 'Username/Email' ) );
				}
			}
			// Return the user back to the correct location
			$destination = $this->_config->get( 'session/login_destination' );
			if ( $destination == 'previous' || $loggedIn === false ) {
				if ( empty( $_SESSION['previous_url'] ) ) {
					$url = $this->_router->makeUrl( '/' );
				} else {
					$url = $_SESSION['previous_url'];
				}
			} else if ( $destination == 'home' ) {
				$url = $this->_router->getBaseUrl();
			} else if ( $destination == 'custom' ) {
				$url = $this->_config->get( 'session/login_destination_url' );
				if ( !zula_url_has_scheme( $url ) ) {
					$url = $this->_router->makeUrl( $url );
				}
			}
			return zula_redirect( $url );
		}

		/**
		 * Logs the user out and then will zula_redirect back to the
		 * appropiate page (normally the previous URL) or the
		 * session index controller
		 *
		 * @return void
		 */
		public function logoutSection() {
			if ( empty( $_SESSION['previous_url'] ) ) {
				$url = $this->_router->makeFullUrl( '/' );
			} else {
				$url = $_SESSION['previous_url'];
			}
			$this->_session->destroy();
			return zula_redirect( $url );
		}

	}

?>
