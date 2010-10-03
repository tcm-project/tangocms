<?php

/**
 * Zula Framework Logging - File. Logs all messagess to a file.
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Log
 */

	class Log_File extends Zula_LibraryBase implements Log_Base {

		/**
		 * Set wether to create a new log file for each day,
		 * prefixed with international standard date
		 * @var bool
		 */
		protected $logDaily = true;

		/**
		 * Defalt log file to write logs to
		 * @var string
		 */
		protected $logFile = 'log_default.txt';

		/**
		 * Directory inwhich the log file(s) will be kept
		 * @var string
		 */
		protected $logDir = './application/logs/'; # Overwritten in constructor

		/**
		 * Maximum time for a log file to live for, will be
		 * deleted after it has expired. Default, 2 weeks
		 * @var int
		 */
		protected $ttl = 1209600;

		/**
		 * Constructor
		 *
		 * Sets whether to log daily and sets the directory to log to
		 */
		public function __construct() {
			if ( $this->_config->has( 'debug/zula_log_daily' ) ) {
				$this->logDaily = (bool) $this->_config->get( 'debug/zula_log_daily' );
			}
			if ( $this->_config->has( 'debug/zula_log_ttl' ) ) {
				$this->ttl = (int) $this->_config->get( 'debug/zula_log_ttl' );
			}
			$this->logDir = $this->_zula->getDir( 'logs' );
			foreach( new DirectoryIterator( $this->logDir ) as $file ) {
				// Remove all *old* log files
				$fnStart = substr( $file->getFileName(), -8 );
				if ( $fnStart == 'zula.log' && $file->isFile() && zula_is_deletable( $file->getPathName() ) && ($file->getCTime() + $this->ttl) < time() ) {
					unlink( $file->getPathName() );
				}
			}
		}

		/**
		 * Main method that is called on all loggers from
		 * the Log class.
		 *
		 * @param string $message
		 * @param int $level
		 * @param string $file
		 * @param int $line
		 * @return bool
		 */
		public function logMessage( $msg, $level, $file='unknown', $line=0 ) {
			$fileName = $this->makeFileName( $level );
			$filePath = $this->logDir.'/'.$fileName;
			if ( !zula_is_writable( $this->logDir ) ) {
				return false;
			}
			$uid = Registry::has( 'session' ) ? $this->_session->getUserId() : 'unknown';
			$msgFormat = '[%1$s] [%2$s | uid %3$s] [%4$s] -- (%5$s:%6$d) %7$s'."\r\n";
			$entry = sprintf( $msgFormat,
							  date( 'c' ),
							  zula_get_client_ip(),
							  $uid,
							  $this->levelName( $level ),
							  basename($file),
							  $line,
							  $msg
							);
			return error_log( $entry, 3, $filePath );
		}

		/**
		 * Makes the correct log file name to be used
		 *
		 * @param int $logLevel
		 * @return string
		 */
		protected function makeFileName( $level ) {
			$fileName = 'zula.log';
			if ( $this->logDaily === true ) {
				// Prefix the file name with the current date
				$fileName = date( 'Y-m-d' ).'_'.$fileName;
			}
			return $fileName;
		}

		/**
		 * Converts a log level int to a correctly worded
		 * version of it, ie 'debug'
		 *
		 * @param int $level
		 * @return string
		 */
		protected function levelName( $level ) {
			switch( $level ) {
				case Log::L_DEBUG:
					return 'debug';

				case Log::L_INFO:
					return 'info';

				case Log::L_WARNING:
					return 'warning';

				case Log::L_ERROR:
				case Log::L_FATAL:
					return 'fatal';

				case Log::L_EVENT:
					return 'event';

				case Log::L_STRICT:
					return 'strict';

				default:
					return 'unknown';
			}
		}

	}

?>
