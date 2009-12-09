<?php
// $Id: aliases.php 2768 2009-11-13 18:12:34Z alexc $

/**
 * Zula Framework Model (Aliases)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Aliases
 */

 	class Aliases_Model extends Zula_ModelBase {

 		/**
 		 * Constants used when getting details for a
 		 * alias, or checking if one exists
 		 */
 		const
			_ID		= 'id', # Get by ID
			_NAME	= 'alias', # By name (name column)
			_URL	= 'url'; # By the URL (value column)

 		/**
 		 * Holds how many aliases would have been returned if no limit
 		 * @var int|bool
 		 */
 		protected $aliasCount = false;

 		/**
 		 * Gets all of the URL Aliases that are avaliable. If
 		 * a limit is passed, then the the number of URL Aliases
 		 * returned will be that of the passed limit (if there
 		 * is enough)
 		 *
 		 * @param int $limit
 		 * @param int $offset
 		 * @return array
 		 */
 		public function getAll( $limit=0, $offset=0 ) {
 			$offset = (int) $offset;
 			if ( !($aliases = $this->_cache->get('aliases')) ) {
				$aliases = array();
 				$query = 'SELECT * FROM {SQL_PREFIX}mod_aliases ORDER BY alias ASC';
 				$this->aliasCount = 0;
				foreach( $this->_sql->query( $query, PDO::FETCH_ASSOC ) as $row ) {
					$aliases[ trim($row['alias'], '/') ] = $row;
					++$this->aliasCount;
 				}
 				$this->_cache->add( 'aliases', $aliases );
 			}
 			if ( $limit == 0 ) {
 				$limit = null;
 			}
 			return array_slice( $aliases, $offset, $limit, true ); 			
 		}

 		/**
 		 * Counts how many URL aliases would have been returned if
 		 * self::getAll() had no limit/offsset
 		 *
 		 * @return int|bool
 		 */
 		public function getCount() {
 			if ( $this->aliasCount === false ) {
 				return count( $this->getAll() );
 			} else {
	 			$count = $this->aliasCount;
				$this->aliasCount = false;
				return $count;
			}
 		}

 		/**
 		 * Checks if a URL Aliases exists, by either ID, name
 		 * or URL
 		 *
 		 * @param mixed $alias
 		 * @param int $where
 		 * @return bool
 		 */
 		public function aliasExists( $alias, $where=self::_ID ) {
 			try {
 				$this->getDetails( $alias, $where );
 				return true;
 			} catch ( Exception $e ) {
 				return false;
 			}
 		}

 		/**
 		 * Gets details for a URL Alias if it exists, by ID
 		 * name or URL
 		 *
 		 * @param int $alias
 		 * @param int $where
 		 * @return array
 		 */
 		public function getDetails( $alias, $where=self::_ID ) {
 			$aliases = $this->getAll();
			foreach( $aliases as $tmpAlias ) {
				if (
					($where == self::_ID && $tmpAlias['id'] == $alias)
					||
					($where == self::_NAME && $tmpAlias['alias'] == $alias)
					||
					($where == self::_URL && $tmpAlias['url'] == $alias)
				) {
					return $tmpAlias;
				}
			}			
 			throw new Alias_NoExist( $alias );
 		}

 		/**
 		 * Adds a new URL Aliases to the database
 		 *
 		 * @param string $alias
 		 * @param string $url
		 * @param bool|int $redirect
 		 * @return bool
 		 */
 		public function add( $alias, $url, $redirect=false ) {
 			try {
 				$aliasDetails = $this->getDetails( $alias, self::_NAME );
 				throw new Alias_AlreadyExists( $alias );
 			} catch ( Alias_NoExist $e ) {
				$pdoSt = $this->_sql->prepare( 'INSERT INTO {SQL_PREFIX}mod_aliases (alias, url, redirect) VALUES(?, ?, ?)' );
				$pdoSt->execute( array($alias, $url, $redirect) );
 				if ( $pdoSt->rowCount() ) {
 					$this->_cache->delete( 'aliases' );
 					Hooks::notifyAll( 'aliases_add', $this->_sql->lastInsertId(), $alias, $url, $redirect );
 					return true;
 				} else {
 					return false;
 				}
 			}
 		}

 		/**
 		 * Edits/Updates a URL alias (Sets Alias and URL)
 		 *
 		 * @param int $id
 		 * @param string $alias
 		 * @param string $url
		 * @param bool|int $redirect
 		 * @return bool
 		 */
 		public function edit( $id, $alias, $url, $redirect ) {
 			$aliasDetails = $this->getDetails( $id );
			$pdoSt = $this->_sql->prepare( 'UPDATE {SQL_PREFIX}mod_aliases SET alias = ?, url = ?, redirect = ? WHERE id = ?' );
			$pdoSt->execute( array($alias, $url, $redirect, $aliasDetails['id']) );
 			$this->_cache->delete( 'aliases' );
 			Hooks::notifyAll( 'aliases_edit', $id, $alias, $url, $redirect );
 			return (bool) $pdoSt->rowCount();
 		}

 		/**
 		 * Deletes 1 or more URL alias by ID
 		 *
 		 * @param int|array $alias
 		 * @return bool
 		 */
 		public function delete( $alias ) {
			$pdoSt = $this->_sql->prepare( 'DELETE FROM {SQL_PREFIX}mod_aliases WHERE id = ?' );
			$delCount = 0;
			foreach( (array) $alias as $id ) {
				$pdoSt->execute( array($id) );
				if ( $pdoSt->rowCount() ) {
					++$delCount;
					Hooks::notifyAll( 'aliases_delete', (int) $id );
				}
			}
 			if ( $delCount ) {
 				$this->_cache->delete( 'aliases' );
 				return true;
 			} else {
 				return false;
 			}
 		}

 	}

 ?>
