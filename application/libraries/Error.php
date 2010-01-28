<?php

/**
 * Zula Framework Error Handling
 * Allows for anything to report errors and they will be stored/reported in the correct way
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Error
 */

	class Error extends Zula_LibraryBase {

		/**
		 * Error level constants for use with the mask
		 */
		const
			_FATAL 		= 'Z:1',
			_ERROR		= 'Z:1', # An error within this class is the same as a fatal error.
			_WARNING	= 'Z:2',
			_NOTICE		= 'Z:4',
			_STRICT		= 'Z:8';

		/**
		 * Toggle error logging on/off
		 * @var bool
		 */
		protected $logErrors = true;

		/**
		 * Toggle if detailed error messags should be shown.
		 * We highly reccomended that you set this to *FALSE* when used in
		 * a production environment.
		 * @var bool
		 */
		protected $detailedError = true;

		/**
		 * Should Notices/Warnings be shown?
		 * @var bool
		 */
		protected $showErrors = false;

		/**
		 * Constructor - Sets the error handler to use
		 * and also updates the properties with the main configuration
		 * values.
		 */
		public function __construct() {
			set_error_handler( array($this, 'errorHandler') );
			// Set configuration value defaults
			$errorConf = array(
								'php_error_level'		=> 'highest', # Special meta-value
								'php_display_errors'	=> 0,
								'zula_detailed_error'	=> 1,
								'zula_log_level'		=> 126,
								'zula_log_daily'		=> 1,
								'zula_show_errors'		=> 1,
								'zula_log_errors'		=> 1,
								);
			if ( $this->_config->has( 'debug' ) ) {
				$tmpConfig = $this->_config->get( 'debug' );
				$errorConf = array_merge( $errorConf, $tmpConfig );
			}
			// Update the settings
			$this->phpLevel( $errorConf['php_error_level'], $errorConf['php_display_errors'] );
			$this->zulaLevel( $errorConf['zula_detailed_error'], $errorConf['zula_show_errors'], $errorConf['zula_log_errors'] );
		}

		/**
		 * Updates PHP Error Handling levles
		 *
		 * @param int|string $errorReporting
		 * @param bool $displayErrors
		 * @return null
		 */
		public function phpLevel( $errorReporting, $displayErrors=null ) {
			if ( $errorReporting == 'highest' || !ctype_digit( $errorReporting ) ) {
				$errorReporting = E_ALL | E_STRICT;
			}
			error_reporting( $errorReporting );
			if ( $displayErrors !== null ) {
				ini_set( 'display_errors', $displayErrors );
			}
		}

		/**
		 * Updates internal Zula error handling levels
		 *
		 * @param bool $detailedErrors
		 * @param bool $displayErrors
		 * @param bool $logErrors
		 * @return null
		 */
		public function zulaLevel( $detailedErrors, $displayErrors=null, $logErrors=null ) {
			$this->detailedErrors = (bool) $detailedErrors;
			if ( $displayErrors !== null ) {
				$this->displayErrors = (bool) $displayErrors;
			}
			if ( $logErrors !== null ) {
				$this->logErrors = (bool) $logErrors;
			}
		}

		/**
		 * The custom error handler which is used with trigger_error()
		 *
		 * @param int $errorNum
		 * @parm string $message
		 * @param string $file
		 * @param int $line
		 * @return bool
		 */
		public function errorHandler( $errorNum, $message, $file, $line ) {
			return $this->report( $message, $errorNum, $file, $line );
		}

		/**
		 * Report an error and handle it correctly
		 *
		 * @param string $message
		 * @param int $level
		 * @param string $file
		 * @param int $line
		 * @param string $title
		 * @return bool
		 */
		public function report( $message, $level, $file=null, $line=null, $title='' ) {
			$file = empty($file) ? 'unknown' : $file;
			$line = empty($line) ? 'unknown' : $line;
			// Stop blank error messages by creating a generic one
			if ( !trim( $message ) ) {
				$message = 'unknown error occured, in "'.$file.':'.$line.'"';
			}
			switch( $level ) {
				case E_USER_NOTICE:
				case self::_NOTICE:
					$this->notice( $message, $file, $line );
					$logLevel = Log::L_INFO;
					break;

				case E_USER_WARNING:
				case self::_WARNING;
					$this->warning( $message, $file, $line );
					$logLevel = Log::L_WARNING;
					break;

				case self::_STRICT;
					$this->strict( $message, $file, $line );
					$logLevel = Log::L_STRICT;
					break;

				case E_USER_ERROR:
				case self::_ERROR:
				case self::_FATAL:
					$this->logError( $message, Log::L_FATAL, $file, $line );
					$this->fatal( $message, $file, $line, $title );
					break;

				default:
					return false;
			}
			// Log the message that has been reported
			$this->logError( $message, $logLevel, $file, $line );
			return true;
		}

		/**
		 * Logs the error that has occurred via the main Logger
		 * if set to log error messages.
		 *
		 * @param string $summary
		 * @param string $details
		 * @param int $level
		 * @return bool
		 */
		protected function logError( $message, $level, $file=null, $line=null ) {
			if ( $this->logErrors && Registry::has( 'log' ) ) {
				return $this->_log->message( $message, $level, $file, $line );
			}
		}

		/**
		 * Handle for 'warnings'
		 *
		 * @param string $message
		 * @param string $file
		 * @param int $line
		 * @return bool
		 */
		protected function warning( $message, $file, $line ) {
			if ( $this->displayErrors ) {
				$format = '<p><strong>Zula Warning:</strong> %1$s - in <strong>%2$s</strong> on line <strong>%3$d</strong>';
				printf( $format, $message, $file, $line );
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Handle for 'notices'
		 *
		 * @param string $message
		 * @param string $file
		 * @param int $line
		 * @return bool
		 */
		protected function notice( $message, $file, $line ) {
			if ( $this->displayErrors ) {
				$format = '<p><strong>Zula Notice:</strong> %1$s - in <strong>%2$s</strong> on line <strong>%3$d</strong>';
				printf( $format, $message, $file, $line );
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Handle for 'strict'
		 *
		 * @param string $message
		 * @param string $file
		 * @param int $line
		 * @return bool
		 */
		protected function strict( $message, $file, $line ) {
			if ( $this->displayErrors ) {
				$format = '<p><strong>Zula Strict:</strong> %1$s - in <strong>%2$s</strong> on line <strong>%3$d</strong>';
				printf( $format, $message, $file, $line );
				return true;
			} else {
				return false;
			}
		}

		/**
	 	 * Handle for 'fatal' errors, uses the standard fatal error view, however
	 	 * it wont use the View library since we can't rely on it at this stage.
	 	 *
		 * @param string $message
		 * @param string $file
		 * @param int $line
		 * @param string $title
		 */
		protected function fatal( $message, $file, $line, $title='' ) {
			$msg = '<h1>Internal Error</h1><p>An internal error has occured and caused the page to halt.</p>';
			if ( $this->displayErrors ) {
				if ( trim( $title ) ) {
					$msg .= '<p>'.$title.'</p>';
				}
				if ( $this->detailedErrors ) {
					$msg .= '<textarea cols="90" rows="10" readonly="readonly" name="error">'.
							$message."\n\nFile: ".$file."\nLine: ".$line.'</textarea>';
				}
			}
			$msg .= '<p>Please refresh the page to try again.</p><hr>
					 <p>For details of this error, please check your log files. View the debug manual page for more information:<br>
						<a href="http://manual.tangocms.org/troubleshooting/debug">Troubleshooting/Debug Manual Page</a>
					 </p>';
			zula_fatal_error( 'Internal Error', $msg );
		}

	}

?>
