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
			while( in_array( $this->statement[$this->i], array( ' ', "\t", "\n", "\r" ) ) ) {
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
			} while( is_numeric( $this->statement[++$this->i] ) );  
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
			} while( preg_match( '/[A-Z]/i', $this->statement[++$this->i] ) );
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
		 * @todo Don't replace in strings
		 * @return null
		 */
		protected function replaceLimitsAndOffsets() {
			if ( stripos($this->statement, 'LIMIT') === false && stripos($this->statement, 'OFFSET') === false ) {
				// No limits or offsets
				return;
			}
			$startLimPos = stripos( $this->statement, 'LIMIT' );
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
			$this->i = 0;
			$this->replaceLimitsAndOffsets();
		}

		/**
		 * Replace UTC_TIMESTAMP() with SYSUTCDATETIME()
		 *
		 * @todo Don't replace in strings
		 * @return null
		 */
		protected function replaceUtcTimestamp() {
			$this->statement = str_replace( 'UTC_TIMESTAMP()', 'SYSUTCDATETIME()', $this->statement );
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
