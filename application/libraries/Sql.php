<?php

/**
 * Zula Framework SQL
 * --- Extends the PDO class to allow for some quick methods and compatibility with
 * older versions of Zula Framework.
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Sql
 */

	class Sql extends PDO {

		/**
		 * Different quoting styles that can be used when using
		 * the quick methods, eg Sql::insert(), Sql::delete().
		 */
		const
				_QUOTE_TABLE	= 1,
				_QUOTE_COL		= 2,
				_QUOTE_BOTH		= 3,
				_QUOTE_NONE		= 0;

		/**
		 * SQL prefix to use
		 * @var string
		 */
		protected $sqlPrefix = null;

		/**
		 * String format used for invalid names
		 * @var string
		 */
		protected $invalidFormat = 'provided %1$s name "%1$2" is not a valid name. Must contain "A-Z", "a-z", "0-9", "_", "-" only, and begin with an Alpha char';

		/**
		 * Creates a new PDO instance
		 *
		 * @param string $type				Simple string such as 'mysql' or a PDO DNS string
		 * @param string $database
		 * @param string $host
		 * @param string $user
		 * @param string $pass
		 * @param string $port
		 * @param array $driverOptions	PDO specific driver options
		 */
		public function __construct( $type, $database='', $host='', $user='', $pass='', $port='', $driverOptions=array() ) {
			if ( empty( $database ) && empty( $host ) ) {
				$dns = $type;
				// Get the driver that is to be used
				$splitDns = explode( ':', $dns );
				$driver = $splitDns[0];
			} else if ( $type == 'mysql' || $type == 'mysqli' ) {
				$driver = 'mysql';
				$dns = sprintf( 'mysql:host=%1$s;dbname=%2$s', $host, $database );
				if ( trim( $port ) ) {
					$dns .= ';port='.$port;
				}
			} else if ( $type == 'pgsql' ) {
				$driver = 'pgsql';
				$port = trim($port) ? $port : 5432;
				$dns = sprintf( 'pgsql:host=%1$s port=%2$s dbname=%3$s user=%3$s password=%4$s', $host, $port, $database, $user, $pass );
			}
			/**
			 * Check the needed PDO driver is there
			 */
			if ( !in_array( $driver, PDO::getAvailableDrivers() ) ) {
				throw new SQL_InvalidDriver( 'PDO driver "'.$driver.'" is not available, ensure it is installed', 20 );
			}
			try {
				if ( empty( $driverOptions ) || !is_array( $driverOptions ) ) {
					$driverOptions = array(
											PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
											);
				}
				if ( $driver == 'mysql' ) {
					$driverOptions[ PDO::MYSQL_ATTR_USE_BUFFERED_QUERY ] = true;
				}
				parent::__construct( $dns, $user, $pass, $driverOptions );
			} catch ( PDOexception $e ) {
				throw new SQL_UnableToConnect( $e->getMessage(), 21 );
			}
		}

		/**
		 * Gets the SQL prefix to be used
		 *
		 * @return string
		 */
		public function getSqlPrefix() {
			return $this->sqlPrefix;
		}

		/**
		 * Sets the SQL Prefix to use
		 *
		 * @param string $prefix
		 * @return bool
		 */
		public function setPrefix( $prefix ) {
			$this->sqlPrefix = $prefix;
			return true;
		}

		/**
		 * Checks if a name is valid for use as a Database table, column
		 * etc name
		 *
		 * @param string $name
		 * @return bool
		 */
		public function checkName( $name ) {
			if ( preg_match( '#[A-Z][A-Z0-9_\-]+#i', $name ) && trim( $name ) == $name ) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Replaces all occurences of {SQL_PREFIX} within the SQL query, however
		 * not when it is within quotes.
		 *
		 * @param string $statement
		 * @return string
		 */
		protected function replacePrefix( $statement ) {
			return preg_replace( '#\G((?>("|\')[^"\']+("|\')|.)*?)\{SQL_PREFIX\}#s', '$1'.$this->getSqlPrefix(), $statement );
		}

		/**
		 * Do a query on the current connection and database
		 *
		 * @param string $statement
		 * @param int $options
		 * @return object
		 */
		public function query( $statement, $options=null ) {
			try {
				$stmt = $this->replacePrefix( $statement );
				return is_int($options) ? parent::query( $stmt, $options ) : parent::query( $stmt );
			} catch ( PDOException $e ) {
				throw new SQL_QueryFailed( $e->getMessage(), 22 );
			}
		}

		/**
		 * Replaces the {SQL_PREFIX} in a prepared query
		 *
		 * @param string $statement
		 * @param array $driverOpts
		 * @return object|bool
		 */
		public function prepare( $statement, $driverOpts=array() ) {
			$stmt = $this->replacePrefix( $statement );
			return is_array($driverOpts) ? parent::prepare( $stmt, $driverOpts ) : parent::prepare( $stmt );
		}

		/**
		 * Replaces the {SQL_PREFIX} in exec
		 *
		 * @param string $statement
		 * @return int|bool
		 */
		public function exec( $statement ) {
			return parent::exec( $this->replacePrefix( $statement ) );
		}

		/**
		 * Quick 'insert' method allows for shorter and eaiser
		 * code for simple insert queries
		 *
		 * @param string $table
		 * @param array $entries
		 * @param int $quoteIdent	Sets if the Table and/or columns should be quoted
		 * @return object
		 */
		public function insert( $table, array $entries, $quoteIdent=self::_QUOTE_BOTH ) {
			$table = $this->getSqlPrefix().$table;
			if ( $this->checkName( $table ) ) {
				if ( $quoteIdent & self::_QUOTE_TABLE ) {
					$table = '`'.$table.'`';
				}
				$queryParts = array();
				foreach( $entries as $col=>$val ) {
					if ( !$this->checkName( $col ) ) {
						// Column has invalid name
						throw new SQL_InvalidName( sprintf( $this->invalidFormat, 'column', $col ), 22 );
					} else if ( $quoteIdent & self::_QUOTE_COL ) {
						$queryParts['col'][] = '`'.$col.'`, ';
					} else {
						$queryParts['col'][] = $col.', ';
					}
					$queryParts['val'][] = '?, ';
				}
				// Create the column and value strings
				$queryParts['col'] = trim( implode( '', $queryParts['col'] ), ', ' );
				$queryParts['val'] = trim( implode( '', $queryParts['val'] ), ', ' );
				$statement = 'INSERT INTO '.$table.' ( '.$queryParts['col'].' ) VALUES ( '.$queryParts['val'].' )';
				// Prepare and execute query
				try {
					$pdoSt = parent::prepare( $statement );
					$pdoSt->execute( array_values( $entries ) );
					return $pdoSt;
				} catch ( PDOException $e ) {
					throw new SQL_QueryFailed( $e->getMessage(), 22 );
				}
			} else {
				throw new SQL_InvalidName( sprintf( $this->invalidFormat, 'table', $table ), 23 );
			}
		}

		/**
		 * Quick 'update' method allows for shorter and easier
		 * code for simple update queries
		 *
		 * @param string $table
		 * @param array $entries
		 * @param array $where
		 * @param int $quoteIdent	Sets if the Table and/or columns should be quoted
		 * @return object
		 */
		public function update( $table, array $entries, $where=array(), $quoteIdent=self::_QUOTE_BOTH ) {
			$table = $this->getSqlPrefix().$table;
			if ( $this->checkName( $table ) ) {
				if ( $quoteIdent & self::_QUOTE_TABLE ) {
					$table = '`'.$table.'`';
				}
				$sql = 'UPDATE '.$table.' SET ';
				// Create the middle section of the query
				$middleSql = '';
				foreach( $entries as $key=>$val ) {
					if ( !$this->checkName( $key ) ) {
						// Column has invalid name
						throw new SQL_InvalidName( sprintf( $this->invalidFormat, 'column', $key ), 23 );
					} else if ( $quoteIdent & self::_QUOTE_COL ) {
						$key = '`'.$key.'`';
					}
					$middleSql .= $key.' = ?, ';
				}
				$sql .= trim( $middleSql, ', ' );
				if ( is_array( $where ) && !empty( $where ) ) {
					// Add where onto the query (Only allows for equals so far)
					$whereSql = '';
					foreach( $where as $key=>$val ) {
						if ( !$this->checkName( $key ) ) {
							// Column has invalid name
							throw new SQL_InvalidName( sprintf( $this->invalidFormat, 'column', $key ), 23 );
						} else if ( $quoteIdent & self::_QUOTE_COL ) {
							$key = '`'.$key.'`';
						}
						$whereSql .= ' AND '.$key.' = ? ';
					}
					$sql .= ' WHERE '.trim( $whereSql, 'AND ' );
				}
				// Prepare and execute query
				try {
					$pdoSt = parent::prepare( $sql );
					$pdoSt->execute( array_merge( array_values($entries), array_values($where) ) );
					return $pdoSt;
				} catch ( PDOException $e ) {
					throw new SQL_QueryFailed( $e->getMessage(), 22 );
				}
			} else {
				throw new SQL_InvalidName( sprintf( $this->invalidFormat, 'table', $table ), 23 );
			}
		}

		/**
		 * Quick 'select' method allows for shorted and easie
		 * code for simple select queries
		 *
		 * @param string $table
		 * @param array $where
		 * @param array $cols
		 * @param int $quoteIdent	Sets if the Table and/or columns should be quoted
		 * @return object
		 */
		public function select( $table, $where=array(), $cols=array(), $quoteIdent=self::_QUOTE_BOTH ) {
			if ( !is_array( $where ) ) {
				$where = array();
			}
			if ( !is_array( $cols ) ) {
				$cols = array();
			}
			$table = $this->getSqlPrefix().$table;
			if ( $this->checkName( $table ) ) {
				if ( $quoteIdent & self::_QUOTE_TABLE ) {
					$table = '`'.$table.'`';
				}
				if ( (empty( $cols ) || !is_array( $cols )) && (empty( $where ) || !is_array( $where )) ) {
					// Run a straight select all on the provided table
					try {
						$pdoSt = parent::prepare( 'SELECT * FROM '.$table );
						$pdoSt->execute();
						return $pdoSt;
					} catch ( PDOException $e ) {
						throw new SQL_QueryFailed( $e->getMessage(), 22 );
					}
				} else {
					/**
					 * Construct the correct query string needed for use with prepared
					 * queries. Depending on the $quoteIdent, the column names may be
					 * quoted correctly.
					 */
					$sql = 'SELECT ';
					if ( is_array( $cols ) && !empty( $cols ) ) {
						// Add the columns onto the query
						foreach( $cols as $key=>$column ) {
							if ( is_array( $column ) ) {
								trigger_error( 'Sql::select() invalid column value type (array)', E_USER_WARNING );
								unset( $cols[ $key ] );
							} else if ( !$this->checkName( $column ) ) {
								// Column has invalid name
								throw new SQL_InvalidName( sprintf( $this->invalidFormat, 'column', $column ), 23 );
							} else if ( $quoteIdent & self::_QUOTE_COL ) {
								$cols[ $key ] = '`'.$column.'`';
							}
						}
						$sql .= trim( implode( ',', $cols ), ', ' );
					} else {
						$sql .= '*';
					}
					$sql .= ' FROM '.$table; # Add the table on
					if ( is_array( $where ) && !empty( $where ) ) {
						// Add Where on
						$whereSql = '';
						foreach( $where as $key=>$val ) {
							if ( is_array( $val ) ) {
								trigger_error( 'Sql::select() invalid where value type (array) for key "'.$key.'"', E_USER_WARNING );
								continue;
							} else if ( !$this->checkName( $key ) ) {
								// Column has invalid name
								throw new SQL_InvalidName( sprintf( $this->invalidFormat, 'column', $key ), 23 );
							} else if ( $quoteIdent & self::_QUOTE_COL ) {
								$key = '`'.$key.'`';
							}
							$whereSql .= ' AND '.$key.' = ?';
						}
						$sql .= ' WHERE '.trim( $whereSql, 'AND ' );
					}
					// Prepare and execute query
					try {
						$pdoSt = parent::prepare( $sql );
						$pdoSt->execute( array_values( $where ) );
						return $pdoSt;
					} catch ( PDOException $e ) {
						throw new SQL_QueryFailed( $e->getMessage(), 22 );
					}
				}
			} else {
				throw new SQL_InvalidName( sprintf( $this->invalidFormat, 'table', $table ), 23 );
			}
		}

		/**
		 * Quick 'deoete' method allows for shorted and easie
		 * code for simple delete queries
		 *
		 * @param string $table
		 * @param array $where
		 * @param int $quoteIdent	Sets if the Table and/or columns should be quoted
		 * @return object
		 */
		public function delete( $table, $where, $quoteIdent=self::_QUOTE_BOTH ) {
			$table = $this->getSqlPrefix().$table;
			if ( !is_array( $where ) || empty( $where ) ) {
				throw new SQL_QueryFailed( 'second argument must be an array' );
			} else if ( $this->checkName( $table ) ) {
				if ( $quoteIdent & self::_QUOTE_TABLE ) {
					$table = '`'.$table.'`';
				}
				$sql = 'DELETE FROM '.$table;
				$whereSql = '';
				foreach( $where as $key=>$val ) {
					if ( is_array( $val ) ) {
						trigger_error( 'Sql::delete() invalid where value type (array) for key "'.$key.'"', E_USER_WARNING );
						continue;
					} else if ( !$this->checkName( $key ) ) {
						// Column has invalid name
						throw new SQL_InvalidName( sprintf( $this->invalidFormat, 'column', $key ), 23 );
					} else if ( $quoteIdent & self::_QUOTE_COL ) {
						$key = '`'.$key.'`';
					}
					$whereSql .= ' AND '.$key.' = ?';
				}
				$sql .= ' WHERE '.trim( $whereSql, 'AND ' );
				// Prepare and execute query
				try {
					$pdoSt = parent::prepare( $sql );
					$pdoSt->execute( array_values( $where ) );
					return $pdoSt;
				} catch ( PDOException $e ) {
					throw new SQL_QueryFailed( $e->getMessage(), 22 );
				}
			} else {
				throw new SQL_InvalidName( sprintf( $this->invalidFormat, 'table', $table ), 23 );
			}
		}

		/**
		 * Run all SQL queries within the specified file, the Queries
		 * are split by the MYSQL ';' delimiter that is outside of any
		 * quotes.
		 *
		 * @param strng $file
		 * @return object
		 */
		public function loadSqlFile( $file ) {
			if ( !is_file( $file ) || !is_readable( $file ) ) {
				throw new Sql_InvalidFile( $file.' does not exist or is not readable' );
			}
			foreach( explode( ";\n", file_get_contents($file) ) as $query ) {
				if ( ($query = trim($query)) ) {
					$this->query( $query )->closeCursor();
				}
			}
			return true;
		}

	}

?>
