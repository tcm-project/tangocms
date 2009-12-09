<?php
// $Id: Sql.php 2806 2009-11-28 17:43:42Z alexc $

/**
 * Zula Framework Configuration SQL
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Config
 */

	class Config_sql_InvalidTable extends Exception {}

	class Config_sql extends Config_base {

		/**
		 * The database we are getting the settings from
		 * @var string
		 */
		protected $sqlTable = null;

		/**
		 * Change the table to be used
		 *
		 * @param string $table
		 * @return bool
		 */
		public function setTable( $table ) {
			if ( $this->_sql->checkName( $table ) ) {
				$this->sqlTable = $table;
			} else {
				throw new Config_sql_InvalidTable( 'table "'.$table.'" has an invalid name' );
			}
		}

		/**
		 * Get all of the configuration values from the provided table
		 *
		 * @param string $table
		 * @return bool
		 */
		public function load( $table ) {
			$this->setTable( $table );
			$configValues = array();
			foreach( $this->_sql->query( 'SELECT * FROM {SQL_PREFIX}'.$table, PDO::FETCH_ASSOC ) as $row ) {
				/**
				 * Create the configuration array into the correct* format that is needed
				 */
				$configSplit = preg_split( '#(?<!\\\)/#', trim( $row['name'], '/') );
				foreach( $configSplit as &$val ) {
					$val = str_replace( '\/', '/', $val );
				}
				$splitCount = count( $configSplit );
				$confKey = array( $configSplit[ $splitCount-1 ] => $row['value'] );

				for( $i = $splitCount-2; $i >=0; $i-- ) {
					$confKey = array( $configSplit[ $i ] => $confKey );
				}
				$configValues = zula_merge_recursive( $configValues, $confKey );
			}
			return $this->setConfigValues( $configValues );
		}

		/**
		 * Adds a new setting into the SQL database
		 *
		 * @param string $confKey
		 * @param string $confVal
		 * @return bool
		 */
		public function add( $confKey, $confVal='' ) {
			parent::add( $confKey, $confVal );
			if ( !is_array( $confKey ) ) {
				$confKey = array( $confKey );
				$confVal = array( $confVal );
			}
			$pdoSt = $this->_sql->prepare( 'INSERT INTO {SQL_PREFIX}'.$this->sqlTable.' ( name, value ) VALUES ( :name, :value )' );
			foreach( $confKey as $key=>$name ) {
				$pdoSt->execute( array(':name' => $name, ':value' => $confVal[ $key ]) );
			}
			return $pdoSt->rowCount();
		}

		/**
		 * Updates a setting in the SQL database
		 *
		 * @param string $confKey
		 * @param string $confVal
		 * @return bool
		 */
		public function update( $confKey, $confVal='' ) {
			parent::update( $confKey, $confVal );
			if ( !is_array( $confKey ) ) {
				$confKey = array( $confKey );
				$confVal = array( $confVal );
			}
			$pdoSt = $this->_sql->prepare( 'UPDATE {SQL_PREFIX}'.$this->sqlTable.' SET value = :value WHERE name = :name' );
			foreach( $confKey as $key=>$name ) {
				$pdoSt->execute( array(':name' => $name, ':value' => $confVal[ $key ]) );
			}
			return true;
		}

		/**
		 * Removes/Deletes a configuration value
		 *
		 * @param string $confKey
		 * @return bool
		 */
		public function delete( $confKey ) {
			parent::delete( $confKey );
			$pdoSt = $this->_sql->prepare( 'DELETE FROM {SQL_PREFIX}'.$this->sqlTable.' WHERE name = :name' );
			foreach( (array) $confKey as $name ) {
				$pdoSt->execute( array(':name' => $name) );
			}
			return $pdoSt->rowCount();;
		}

	}

?>
