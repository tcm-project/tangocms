<?php

/**
 * Zula Framework Core
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula
 */

	/** Exceptions */
	class Zula_Exception extends Exception {}

	class Zula_ModelNoExist extends Zula_Exception {}
	class Zula_DetailNoExist extends Zula_Exception {}

	final class Zula {

		/**
		 * Current version of Zula being used
		 */
		const _VERSION = '0.7.71';

		/**
		 * Holds the singleton instance of this class
		 * @var object
		 */
		static private $_instance = null;

		/**
		 * Holds the current working directory
		 * @var string
		 */
		private $cwd = '';

		/**
		 * Array with directory paths to use throughout scripts.
		 * Allows you to easily change the directories used without having
		 * to edit all of the code in many different places
		 * @var array
		 */
		private $directories = array(
									'3rd_party'	=> '/libraries/3rd_party',
									'assets'	=> './assets',
									'config'	=> './config',
									'fonts' 	=> '/fonts',
									'install'	=> './install',
									'js'		=> './assets/js',
									'libs'		=> '/libraries',
									'locale'	=> '/locale',
									'logs'		=> '/logs',
									'modules'	=> '/modules',
									'themes'	=> './assets/themes',
									'tmp'		=> './tmp',
									'uploads'	=> './assets/uploads',
									'views'		=> '/views',
									'zula'		=> '/zula',
									);

		/**
		 * Array with html paths for directories
		 * @var array
		 */
		private $htmlDirs = array();

		/**
		 * The default libraries the the framework will load upon startup
		 * @var array
		 */
		private $defaultLibs = array('date', 'log', 'i18n', 'cache', 'dispatcher', 'error', 'input');

		/**
		 * Path to the main configuration file that Zula will use
		 * @var string
		 */
		private $configFile = '';

		/**
		 * Updates the current directories we have with the correct path and
		 * also does some other needed startup things such as setting the autoloader
		 * storing CWD, getting temp dir etc etc
		 *
		 * @return object
		 */
		private function __construct() {
			$this->cwd = getcwd();
			/**
			 * Set some defaults such as the autoloader
			 */
			set_exception_handler( array($this, 'exceptionHandler') );
			// Configure the base directory
			$base = trim( dirname($_SERVER['SCRIPT_NAME']), './\ ' );
			define( '_BASE_DIR', empty($base) ? '/' : '/'.$base.'/' );
			// Update each directory by prefixing with the _PATH_APPLICATION constant
			foreach( $this->directories as $name=>$path ) {
				if ( substr( $path, 0, 2 ) !== './' ) {
					$path = _PATH_APPLICATION.$path;
				}
				$this->updateDir( $name, $path );
			}
			spl_autoload_register( array(__CLASS__, 'autoloadClass') );
			// Set default path for configuration file
			$this->configFile = $this->getDir( 'config' ).'/default/config.ini.php';
			define( '_AJAX_REQUEST', zula_http_header_get('X-Requested-With') == 'XMLHttpRequest' );
		}

		/**
		 * Get the instance of the Zula class
		 *
		 * @return object
		 */
		static public function getInstance() {
			if ( !is_object( self::$_instance ) ) {
				self::$_instance = new self;
			}
			return self::$_instance;
		}

		/**
		 * Autoloader function that will attempt to load the correct class library
		 * based on the name given.
		 *
		 * It will also automagcailly include an 'Exceptions.php' file if it is found.
		 *
		 * @param string $class
		 */
		static public function autoloadClass( $class ) {
			static $dirs = array();
			if ( empty( $dirs ) ) {
				$dirs = array(
							'modules'	=> realpath( self::$_instance->getDir( 'modules' ) ),
							'libs'		=> realpath( self::$_instance->getDir( 'libs' ) ),
							);
			}
			if ( stripos( $class, '_controller_' ) !== false ) {
				$classSplit = explode( '_', strtolower( $class ) );
				$cntrlrIndex = array_search( 'controller', $classSplit );
				// Store cntrlr file and exception file paths
				$modDir = $dirs['modules'].'/'.implode( '_', array_slice($classSplit, 0, $cntrlrIndex) );
				$classFile = $modDir.'/controllers/'.implode( '_', array_slice($classSplit, $cntrlrIndex+1) ).'.php';
				$exceptions = $modDir.'/Exceptions.php';
			} else {
				// Load internal Zula lib
				$classSplit = array_map( 'ucfirst', explode('_', strtolower($class)) );
				$classFile = $dirs['libs'].'/'.implode( '/', $classSplit ).'.php';
				$exceptions = $dirs['libs'].'/'.$classSplit[0].'/Exceptions.php';
			}
			if ( isset( $exceptions ) && is_readable( $exceptions ) ) {
				include_once $exceptions;
			}
			if ( is_readable( $classFile ) ) {
				include $classFile;
			}
		}

		/**
		 * Zula Exception Handler for all uncaught exceptions
		 *
		 * @param object $exception
		 * @return bool
		 */
		public function exceptionHandler( Exception $e ) {
			$formatCode = sprintf( 'ZULA-%03d', $e->getCode() );
			if ( !Registry::has( 'error' ) ) {
				try {
					$this->loadLib( 'error' );
				} catch ( Exception $e ) {}
			}
			/**
			 * Create the correct title for the exception
			 */
			if ( $e instanceof Zula_Exception ) {
				$title = 'Uncaught internal (Zula) exception';
			} else if ( $e instanceof PDOException ) {
				$title = 'Uncaught SQL (PDO) exception';
			} else {
				$title = 'Uncaught application exception';
			}
			// Attempt to write a dump log file
			$logDir = $this->getDir( 'logs' );
			if ( zula_is_writable( $logDir ) ) {
				$i = 1;
				do {
					$file = $logDir.'/zula-dump.'.$i.'.log';
					$i++;
				} while ( file_exists( $file ) );
				$body = 'Uncaught exception in "'.$e->getFile().'" on line '.$e->getLine().' with code "'.$formatCode.'"';
				$body .= "\n\nProject Version: ".(defined('_PROJECT_VERSION') ? _PROJECT_VERSION : 'unknown');
				$body .= "\nTime & Date: ".date( 'c' );
				$body .= "\nRequest Method: ".$_SERVER['REQUEST_METHOD'];
				if ( Registry::has( 'router' ) ) {
					$body .= "\nRequest Path: ".Registry::get( 'router' )->getRequestPath();
					$body .= "\nRaw Request Path: ".Registry::get( 'router' )->getRawRequestPath();
				}
				$body .= "\nException Thrown: ".get_class( $e );
				$body .= "\nMessage: ".$e->getMessage();
				$body .= "\n\nStack Trace:\n";
				$body .= $e->getTraceAsString();
				file_put_contents( $file, $body );
			}
			if ( Registry::has( 'error' ) ) {
				return Registry::get( 'error' )->report( $e->getMessage(), E_USER_ERROR, $e->getFile(), $e->getLine(), $title );
			} else {
				trigger_error( $e->getMessage(), E_USER_ERROR );
			}
		}

		/**
		 * Loads the default most commonly used libraries for the framework
		 *
		 * @return object
		 */
		public function loadDefaultLibs() {
			$config = Registry::get( 'config' );
			foreach( $this->defaultLibs as $library ) {
				if ( $library == 'cache' ) {
					try {
						$cache = Cache::factory( $config->get( 'cache/type' ) );
					} catch ( Config_KeyNoExist $e ) {
						// Revert to file based caching.
						$cache = Cache::factory( 'file' );
					}
					try {
						$cache->ttl( $config->get( 'cache/ttl' ) );
					} catch ( Exception $e ) {}
				} else if ( $library == 'i18n' ) {
					try {
						I18n::factory( $config->get( 'locale/engine' ) );
					} catch ( Config_KeyNoExist $e ) {
						I18n::factory( 'failsafe' );
					}
				} else {
					$this->loadLib( $library );
				}
			}
			return $this;
		}

		/**
		 * Loads a library by doing the following:
		 * 1) Load the class file
		 * 2) Create instance of class
		 * 3) Call it's _onLoad() method if it exists
		 * 4) Adding it to the registry
		 *
		 * If the library is already loaded then it wont be loaded again
		 *
		 * @param string $library
		 * @praam string $regName	Custom name to be used when storing in the registry
		 * @return object
		 */
		public function loadLib( $library, $regName=null ) {
			$className = strtolower( $library );
			$regName = trim($regName) ? $regName : $className;
			if ( Registry::has( $regName ) ) {
				return Registry::get( $regName );
			}
			$tmpLib = new $className;
			if ( $tmpLib instanceof Zula_LibraryBase ) {
				if ( method_exists( $tmpLib, '_onLoad' ) ) {
					$tmpLib->_onLoad( $regName );
				}
				Registry::register( $regName, $tmpLib );
				return $tmpLib;
			} else {
				throw new Zula_Exception( 'Zula library "'.$className.'" must extend "Zula_LibraryBase"', 2 );
			}
		}

		/**
		 * Gets the path to the main configuration file
		 *
		 * @return string
		 */
		public function getConfigPath() {
			return $this->configFile;
		}

		/**
		 * Loads the main configuration file and adds it to the library
		 *
		 * @param string $configFile
		 * @return object
		 */
		public function loadMainConfig( $configFile=null ) {
			if ( Registry::has( 'config' ) && Registry::has( 'config_ini' ) ) {
				return Registry::get( 'config' );
			} else if ( empty( $configFile ) || !is_readable( $configFile ) ) {
				$configFile = $this->getConfigPath();
			}
			// Set the config file for what we are using
			$this->configFile = $configFile;
			try {
				$configIni = new Config_ini;
				$configIni->load( $configFile );
				Registry::register( 'config_ini', $configIni );
				// Merge the ini configuration in to the main config library
				$config = new Config;
				$config->load( $configIni );
				Registry::register( 'config', $config );
			} catch ( Config_Ini_FileNoExist $e ) {
				throw new Zula_Exception( 'Zula configuration file "'.$configFile.'" does not exist or is not readable', 8);
			}
			return $config;
		}

		/**
		 * Updates the path to a named directory that can be used. If the path
		 * does not exist, it shall attemp to create the directory (only if the
		 * 3rd argument is set to bool true)
		 *
		 * @param string $name
		 * @param string $dir
		 * @param bool $createDir
		 * @return object
		 */
		public function setDir( $name, $dir, $createDir=false ) {
			if ( $createDir && zula_make_dir( $dir ) === false ) {
				throw new Zula_Exception( 'failed to create directory "'.$dir.'"' );
			}
			$this->directories[ $name ] = $dir;
			unset( $this->htmlDirs[ $name ] );
			return $this;
		}

		/**
		 * Alias to Zula::setDir()
		 *
		 * @param string $name
		 * @param string $dir
		 * @param bool $createDir
		 * @deprecated deprecated since 0.7.70
		 * @return object
		 */
		public function updateDir( $name, $dir, $createDir=false ) {
			return $this->setDir( $name, $dir, $createDir );
		}

		/**
		 * Returns the correct dir specified. It set it will remove
		 * all dots and slashes from the right. If set to return it
		 * for use in HTML (or other places I guess) then it will return
		 * a string that contains the correct URL for use in HTML.
		 *
		 * For example, if installed to example.com/tangocms/ then it will return
		 * a string such as '/tangocms/application/libraries' instead of just
		 * './application/libraries'
		 *
		 * This method does *NOT* return a string with a forward slash as its suffex:
		 * IE: it will return './html/images' *NOT* './html/images/'
		 *
		 * @param string $name
		 * @param bool $forHtml
		 * @return string
		 */
		public function getDir( $name, $forHtml=false ) {
			if ( isset( $this->directories[ $name ] ) ) {
				if ( $forHtml === true ) {
					if ( !isset( $this->htmlDirs[ $name ] ) ) {
						$trim = (substr( $this->directories[ $name ], 0, 2 ) == '..') ? '/\ ' : './\ ';
						$this->htmlDirs[ $name ] = _BASE_DIR . trim( $this->directories[ $name ], $trim );
					}
					return $this->htmlDirs[ $name ];
				}
				return rtrim( $this->directories[ $name ], '/' );
			}
			throw new Zula_Exception( 'Zula named directory "'.$name.'" does not exist' );
		}

		/**
		 * A handy function to reset the current working directory to what it
		 * was at the very beggining of the script. This is because with Apache
		 * the CWD is reset to / within a class Destructor.
		 *
		 * @return bool
		 */
		public function resetCwd() {
			return chdir( $this->cwd );
		}

	}
?>
