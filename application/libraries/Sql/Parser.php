<?php

/**
 * Zula Framework
 * Parse a (simple) MySQL string and convert it as needed to another
 * database type. Assumes all statements are valid
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Robert Clipsham
 * @copyright Copyright (C) 2010 Robert Clipsham
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Sql
 */

	class Sql_Parser {

		/**
		 * Database type
		 * @var string
		 */
		protected $dbType = 'mysql';

		/**
		 * Current statement to parse
		 * @var string
		 */
		protected $statement = '';

		/**
		 * Current index
		 * @var int
		 */
		protected $i = 0;

		/**
		 * Constructor function
		 *
		 * @param string $dbType - The database type
		 */
		public function __construct( $dbType ) {
			$this->dbType = $dbType;
		}

		/**
		 * Parse the SQL
		 *
		 * @return string
		 */
		public function parse( $statement ) {
			$this->statement = $statement;
			switch( $this->dbType ) {
				case 'mysql':
					return $statement;
				case 'sqlsrv':
					$this->parseSqlsrv();
					return $this->statement;
				default:
					throw new Sql_UnsupportedDbType;
					break;
			}
		}

		/**
		 * Skip over whitespace in a string
		 *
		 * @return null
		 */
		protected function eatSpace() {
			while( isset( $this->statement[$this->i] ) && in_array( $this->statement[$this->i], array( ' ', "\t", "\n", "\r" ) ) ) {
				$this->i++;
			}
		}

		/**
		 * Eat a number and return it
		 *
		 * @return string
		 */
		protected function eatNumber() {
			$num = '';
			do {
				$num .= $this->statement[$this->i];
			} while( isset( $this->statement[++$this->i] ) && is_numeric( $this->statement[$this->i] ) );  
			return $num;
		}

		/**
		 * Eat a named parameter
		 *
		 * @return string
		 */
		protected function eatNamedParameter() {
			$np = '';
			do {
				$np .= $this->statement[$this->i];
			} while( isset( $this->statement[++$this->i] ) && preg_match( '/[A-Z]/i', $this->statement[$this->i] ) );
			return $np;
		}

		/**
		 * Eat either a named parameter or number
		 *
		 * @return string
		 */
		protected function eatNpOrNumber() {
			if ( $this->statement[$this->i] == ':' ) {
				return $this->eatNamedParameter();
			} else if( is_numeric( $this->statement[$this->i] ) ) {
				return $this->eatNumber();
			}
			throw new Sql_ParserException;
		}

		/**
		 * Replaces `` with [] except in strings
		 *
		 * @return null
		 */
		protected function replaceSqlsrvQuotes() {
			for(; $this->i < strlen( $this->statement ); $this->i++ ) {
				switch( $this->statement[$this->i] ) {
					case '\'':
					case '"':
						$char = $this->statement[$this->i];
						do {
							$this->i++;
						} while( $this->statement[$this->i] != $char );
						break;
					case '`':
						$this->statement[$this->i] = '[';
						do {
							$this->i++;
						} while( $this->statement[$this->i] != '`' );
						$this->statement[$this->i] = ']';
						break;
					default:
						break;
				}
			}
			$this->i = 0;
		}

		/**
		 * Replace LIMIT x [OFFSET y]
		 *
		 * @return null
		 */
		protected function replaceLimitsAndOffsets() {
			for(; $this->i < strlen( $this->statement ); $this->i++ ) {
				switch( $this->statement[$this->i] ) {
					case '\'':
					case '"':
						$char = $this->statement[$this->i];
						do {
							$this->i++;
						} while( $this->statement[$this->i] != $char );
						break;
					case 'L':
						if ( substr( $this->statement, $this->i, 5 ) != 'LIMIT' ) {
							break;
						}
						$startLimPos = $this->i;
						$this->i = $startLimPos + 5;
						$this->eatSpace();
						$arg1 = $this->eatNpOrNumber();
						$this->eatSpace();
						if ( substr( $this->statement, $this->i, 6 ) != 'OFFSET' ) {
							$this->statement = substr($this->statement, 0, $startLimPos).
										'ROW_NUMBER() OVER(ORDER BY(SELECT 1)) > '.$arg1.' '.
										substr($this->statement, $this->i);
						} else {
							$this->i += 6;
							$this->eatSpace();
							$arg2 = $this->eatNpOrNumber();
							$this->statement = substr($this->statement, 0, $startLimPos).
										'ROW_NUMBER() OVER(ORDER BY(SELECT 1)) BETWEEN '.$arg2.' AND '.$arg2.'+'.$arg1.
										substr($this->statement, $this->i);
						}
						break;
					default:
						break;
				}
			}
			$this->i = 0;
		}

		/**
		 * Replace UTC_TIMESTAMP() and NOW() with SYSUTCDATETIME()
		 *
		 * @return null
		 */
		protected function replaceUtcTimestamp() {
			for(; $this->i < strlen( $this->statement ); $this->i++ ) {
				$plus = 0;
				switch( $this->statement[$this->i] ) {
					case '\'':
					case '"':
						$char = $this->statement[$this->i];
						do {
							$this->i++;
						} while( $this->statement[$this->i] != $char );
						break;
					case 'N':
						$plus = 5;
					case 'U':
						if ( substr( $this->statement, $this->i, 15 ) != 'UTC_TIMESTAMP()' &&
							substr( $this->statement, $this->i, 5 ) != 'NOW()') {
							break;
						}
						$plus = $plus == 5 ? 5 : 15;
						$this->statement = substr($this->statement, 0, $this->i).
									'SYSUTCDATETIME()'.
									substr($this->statement, $this->i += $plus);
						break;
					default:
						break;
				}
			}
			$this->i = 0;
		}

		/**
		 * Parse and replace with SQL Server SQL
		 *  - Replaces LIMIT, OFFSET with ROW_NUMBER() OVER()
		 *
		 * @return string
		 */
		protected function parseSqlsrv() {
			$this->replaceSqlsrvQuotes();
			$this->replaceLimitsAndOffsets();
			$this->replaceUtcTimestamp();
		}
	}

?>
