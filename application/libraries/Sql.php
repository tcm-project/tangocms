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
		 * @param string $driver PDO driver type or a PDO DSN string
		 * @param string $dbname
		 * @param string $host
		 * @param string $user
		 * @param string $pass
		 * @param string $port
		 * @param array $opts PDO specific driver options
		 */
		public function __construct( $driver, $dbname='', $host='', $user='', $pass='', $port='', array $opts=array() ) {
			if ( !$dbname && !$host ) {
				$dsn = $driver;
				// Get the driver that is to be used
				$splitDsn = explode( ':', $dsn );
				$driver = $splitDsn[0];
			} else if ( $driver == 'mysql' ) {
				$dsn = sprintf( 'mysql:host=%1$s;dbname=%2$s', $host, $dbname );
				if ( $port ) {
					$dsn .= ';port='.(int) $port;
				}
			} else if ( $driver == 'pgsql' ) {
				if ( !$port ) {
					$port = 5432;
				}
				$dsn = sprintf( 'pgsql:host=%1$s port=%2$d dbname=%3$s user=%3$s password=%4$s',
						$host, $port, $dbname, $user, $pass );
			} else if ( $driver == 'sqlsrv' ) {
				if ( !$port ) {
					$port = 1433;
				}
				$dsn = sprintf( 'sqlsrv:server=%1$s,%2$d;database=%3$s', $host, $port, $dbname );
			}
			if ( !in_array( $driver, PDO::getAvailableDrivers() ) ) {
				throw new SQL_InvalidDriver( 'PDO driver "'.$driver.'" is not available, ensure it is installed', 20 );
			}
			try {
				$opts += array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
				if ( $driver == 'mysql' ) {
					$opts[ PDO::MYSQL_ATTR_USE_BUFFERED_QUERY ] = true;
					$opts[ PDO::MYSQL_ATTR_INIT_COMMAND ] = 'SET NAMES "utf8"';
				}
				parent::__construct( $dsn, $user, $pass, $opts );
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
		 * Replaces all occurences of {PREFIX} or {SQL_PREFIX} within
		 * the SQL query.
		 *
		 * @param string $statement
		 * @return string
		 */
		protected function replacePrefix( $statement ) {
			return str_replace( array('{PREFIX}', '{SQL_PREFIX}'), $this->getSqlPrefix(), $statement );
		}

		/**
		 * Changes the quote identifier character to be compatible with the
		 * current database type, eg `` to [] for sqlsrv
		 *
		 * @param string $statement
		 * @return string
		 */
		protected function fixIdentifierQuote( $statement ) {
			switch( $this->getAttribute( PDO::ATTR_DRIVER_NAME ) ) {
				case 'sqlsrv':
					for( $i = 0; $i < strlen( $statement ); $i++ ) {
						switch( $statement[$i] ) {
							case '\'':
							case '"':
								$char = $statement[$i];
								do {
									$i++;
								} while( $statement[$i] != $char );
								break;
							case '`':
								$statement[$i] = '[';
								do {
									$i++;
								} while( $statement[$i] != '`' );
								$statement[$i] = ']';
								break;
							default:
								break;
						}
					}
					break;
				case 'mysql':
				default:
					break;
			}
			return $statement;
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
				$stmt = $this->fixIdentiferQuote( $this->replacePrefix( $statement ) );
				return is_int($options) ? parent::query( $stmt, $options ) : parent::query( $stmt );
			} catch ( PDOException $e ) {
				throw new SQL_QueryFailed( $e->getMessage(), 22 );
			}
		}

		/**
		 * Replaces the {PREFIX} in a prepared query
		 *
		 * @param string $statement
		 * @param array $driverOpts
		 * @return object|bool
		 */
		public function prepare( $statement, $driverOpts=array() ) {
			$stmt = $this->fixIdentifierQuote( $this->replacePrefix( $statement ) );
			return is_array($driverOpts) ? parent::prepare( $stmt, $driverOpts ) : parent::prepare( $stmt );
		}

		/**
		 * Replaces the {PREFIX} in exec
		 *
		 * @param string $statement
		 * @return int|bool
		 */
		public function exec( $statement ) {
			return parent::exec( $this->fixIdentifierQuote( $this->replacePrefix( $statement ) ) );
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
					$pdoSt = parent::prepare( $this->fixIdentifierQuote($statement) );
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
					$pdoSt = parent::prepare( $this->fixIdentifierQuote($sql) );
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
		 * Quick 'select' method allows for shorter and easier
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
						$pdoSt = parent::prepare( $this->fixIdentifierQuote($sql) );
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
		 * Quick 'delete' method allows for shorter and easier
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
					$pdoSt = parent::prepare( $this->fixIdentifierQuote( $sql ) );
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
		 * Run all SQL queries within the specified file
		 *
		 * @param string $directory
		 * @param string $file - filename without the .sql
		 * @return object
		 */
		public function loadSqlFile( $directory, $file ) {
			$fallbackFile = sprintf( '%s/%s.sql',	rtrim( $directory, '/' ),
								$file );
			$file = sprintf( '%s/%s.%s.sql', rtrim( $directory, '/' ),
							$file,
							$this->getAttribute( PDO::ATTR_DRIVER_NAME ) );
							
			if ( !is_file( $file ) || !is_readable( $file ) ) {
				if ( !is_file( $fallbackFile ) || !is_readable( $fallbackFile ) ) {
					throw new Sql_InvalidFile( $file.' does not exist or is not readable' );
				}
				$file = $fallbackFile;
			}
			$result = $this->query( file_get_contents( $file ) );
			if ( $result instanceof PDOStatement ) {
				$result->closeCursor();
			}
			return true;
		}

	}

?>
