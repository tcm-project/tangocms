<?php

/**
 * Zula Framework Configuration Base
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Config
 */

	abstract class Config_base extends Zula_LibraryBase {

		/**
		 * Array of all the configuration values loaded
		 * @var array
		 */
		private $configValues = array();

		/**
		 * An array of cached "already got" config values
		 * @var array
		 */
		public $cachedConfigValues = array();

		/**
		 * Constructor
		 */
		public function __construct() {
		}

		/**
		 * Abstract method which will be used to load the configuration values
		 * from the appropiate source
		 *
		 * @param mixed $source
		 * @return bool
		 */
		abstract public function load( $source );

		/**
		 * Sets the configuration values that should have
		 * come from one of the Config classes (XML, INI, SQL etc)
		 *
		 * @param array $config
		 * @return bool
		 */
		protected function setConfigValues( array $config ) {
			$this->configValues = $config;
			return true;
		}

		/**
		 * Returns the entire configuration array
		 *
		 * @return array
		 */
		public function getAll() {
			return $this->configValues;
		}

		/**
		 * Get a single configuration value that is stored (hopefully).
		 *
		 * @return mixed
		 */
		public function get( $confKey ) {
			if ( !isset( $this->cachedConfigValues[ $confKey ] ) ) {
				if ( !is_string( $confKey ) ) {
					trigger_error( 'Config_base::get() unable to get configuration value, key given is not a string', E_USER_NOTICE );
					return false;
				}
				$configSplit = preg_split( '#(?<!\\\)/#', trim( $confKey, '/') );
				foreach( $configSplit as $val ) {
					$val = stripslashes( $val );
					if ( !isset( $tmpConfVal ) && isset( $this->configValues[ $val ] ) ) {
						$tmpConfVal = $this->configValues[ $val ];
					} else if ( isset( $tmpConfVal ) && is_array( $tmpConfVal ) && isset( $tmpConfVal[ $val ] ) ) {
						$tmpConfVal = $tmpConfVal[ $val ];
					} else {
						throw new Config_KeyNoExist( 'configuration value "'.stripslashes($confKey).'" does not exist' );
					}
				}
				zula_normalize( $tmpConfVal );
				$this->cachedConfigValues[ $confKey ] = $tmpConfVal;
			}
			return $this->cachedConfigValues[ $confKey ];
		}

		/**
		 * Checks if a config value exist
		 *
		 * @param string $confKey
		 * @return bool
		 */
		public function has( $confKey ) {
			try {
				$this->get( $confKey );
				return true;
			} catch( Config_KeyNoExist $e ) {
				return false;
			}
		}

		/**
		 * Updates a config value in the stored configuration array
		 *
		 * If an array is passed then it can update multiple items
		 * at once with the corrosponding value
		 *
		 * @param mixed $confKey
		 * @param mixed $confVal
		 * @return bool
		 */
		public function update( $confKey, $confVal='' ) {
			if ( !is_array( $confKey ) ) {
				$confKey = array( $confKey );
				$confVal = array( $confVal );
			}
			foreach( $confKey as $key=>$configKey ) {
				if ( !$this->has( $configKey ) ) {
					throw new Config_KeyNoExist( 'unable to update configuration value "'.$configKey.'" as it does not exist' );
				}
				if ( isset( $this->cachedConfigValues[ $configKey ] ) ) {
					unset( $this->cachedConfigValues[ $configKey ] );
				}
				/**
				 * Trim leading and ending slashes, also stripslashes
				 * from the configuration key if needbe
				 */
				$parts = preg_split( '#(?<!\\\)/#', trim( $configKey, '/' ) );
				$level = &$this->configValues;
				for ( $i=0; $i < count( $parts ); $i++ ) {
					$part = stripslashes( $parts[ $i ] );
					if ( $i == count( $parts )-1 ) {
						$level[ $part ] = $confVal[ $key ];
					} else {
						$level = &$level[ $part ];
					}
				}
			}
			return true;
		}

		/**
		 * Removes a configuration value if it exists
		 *
		 * @param mixed $confKey
		 * @return int
		 */
		public function delete( $confKey ) {
			$numDel = 0;
			foreach( (array) $confKey as $configKey ) {
				if ( $this->has( $configKey ) ) {
					if ( isset( $this->cachedConfigValues[ $configKey ] ) ) {
						unset( $this->cachedConfigValues[ $configKey ] );
					}
					/**
					* Trim leading and ending slashes, also stripslashes
					* from the configuration key if needbe
					*/
					$parts = preg_split( '#(?<!\\\)/#', trim( $configKey, '/' ) );
					$level = &$this->configValues;
					for ( $i=0; $i < count( $parts ); $i++ ) {
						$part = stripslashes( $parts[ $i ] );
						if ( $i == count( $parts )-1 ) {
							unset( $level[ $part ] );
						} else {
							$level = &$level[ $part ];
						}
					}
					++$numDel;
				}
			}
			return $numDel;
		}

		/**
		 * Adds new settings to the configuration array
		 *
		 * @param mixed $confKey
		 * @param mixed $confVal
		 * @return int
		 */
		public function add( $confKey, $confVal='' ) {
			if ( !is_array( $confKey ) ) {
				$confKey = array( $confKey );
				$confVal = array( $confVal );
			}
			$numAdded = 0;
			foreach( $confKey as $key=>$configKey ) {
				if ( !$this->has( $configKey ) ) {
					/**
					* Trim leading and ending slashes, also stripslashes
					* from the configuration key if needbe
					*/
					$parts = preg_split( '#(?<!\\\)/#', trim( $configKey, '/' ) );
					$level = &$this->configValues;
					for( $i=0; $i < count( $parts ); $i++ ) {
						$part = stripslashes( $parts[ $i ] );
						if ( $i == count( $parts )-1 ) {
							$level[ $part ] = $confVal[ $key ];
						} else {
							if ( !isset( $level[ $part ] ) ) {
								$level[ $part ] = array();
							}
							$level = &$level[ $part ];
						}
					}
					++$numAdded;
				}
			}
			return $numAdded;
		}

	}

?>
