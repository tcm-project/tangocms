<?php

/**
 * Zula Framework Logging
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Log
 */

	class Log extends Zula_LibraryBase {

		/**
		 * Define the different log levels
		 */
		const
			L_DEBUG   	= 1,
			L_INFO    	= 2,
			L_NOTICE  	= 2, // Alias to L_INFO
			L_WARNING 	= 4,
			L_ERROR   	= 8,
			L_FATAL   	= 16, // Fatal should only really be logged by an Error Handler
			L_EVENT   	= 32,
			L_STRICT	= 64,

			L_ALL 		= 127;

		/**
		 * Log mask to use when seeing if a message should be logged
		 * @param int
		 */
		protected $logMask = 62;

		/**
		 * All of the loggers that have been attached
		 * @var array
		 */
		protected $loggers = array();

		/**
		 * Constructor
		 * Sets up the default logger (file) ready to use
		 */
		public function __construct() {
			$defaultLogger = new Log_File;
			$this->addLogger( $defaultLogger, 'file' );
			$this->setLogMask( $this->_config->get( 'debug/zula_log_level' ) );
		}

		/**
		 * Sets the log mask to use
		 *
		 * @param int $logMask
		 * @return bool
		 */
		public function setLogMask( $logMask ) {
			if ( ctype_digit( $logMask ) || is_int( $logMask ) ) {
				$this->logMask = $logMask;
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Adds a new logger that will be used to log a message
		 *
		 * @param object $logger
		 * @param string $name	Name of the logger
		 * @return bool
		 */
		public function addLogger( Log_base $logger, $name='' ) {
			if ( empty( $name ) ) {
				$this->loggers[] = $logger;
			} else {
				$this->loggers[ $name ] = $logger;
			}
			return true;
		}

		/**
		 * Removes a logger if it exists
		 *
		 * @param string $name
		 * @return bool
		 */
		public function removeLogger( $name ) {
			if ( empty( $name ) ) {
				return false;
			} else if ( isset( $this->loggers[ $name ] ) ) {
				unset( $this->loggers[ $name ] );
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Log a message by notifying all of the loggers
		 * that they need to wake up and do some logging.
		 *
		 * @param string $message
		 * @param int $level
		 * @param string $file
		 * @param int $line
		 * @param array $loggers	Choose which loggers should be notified
		 * @return bool
		 */
		public function message( $message, $level, $file='unknown', $line=0, array $loggers=array() ) {
			if ( $this->logMask & $level ) {
				foreach( $this->loggers as $logger=>$obj ) {
					if ( empty( $loggers ) || in_array( $logger, $loggers ) ) {
						$obj->logMessage( $message, $level, $file, $line );
					}
				}
				return true;
			} else {
				return false;
			}
		}

	}

?>
