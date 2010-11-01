<?php

/**
 * Zula Framework Module
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Page
 */

	class Page_model extends Zula_ModelBase {

		/**
 		 * Contains how many pages would have been
		 * returned with no limit
 		 * @var int
 		 */
 		protected $pageCount = null;

		/**
		 * Gets details for page that exists, and can return a
		 * subset of the results. Can also check if user has
		 * access to the form.
		 *
		 * @param int $limit
		 * @param int $offset
		 * @param int $parent
		 * @param bool $aclCheck
		 * @param bool $withBody
		 * @return array
		 */
		public function getAllPages( $limit=0, $offset=0, $parent=0, $aclCheck=true, $withBody=false ) {
			$cols = $withBody ? '*' : 'id, title, author, date, parent, `order`, identifier';
			$statement = 'SELECT SQL_CALC_FOUND_ROWS '.$cols.' FROM {PREFIX}mod_page
						  WHERE parent = '.(int) $parent.' ORDER BY title ASC';
			if ( $limit != 0 || $offset != 0 ) {
				// Limit the result set.
				$params = array();
				if ( $limit > 0 ) {
					$statement .= ' LIMIT :limit';
					$params[':limit'] = $limit;
				} else if ( $limit == 0 && $offset > 0 ) {
					$statement .= ' LIMIT 1000000';
				}
				if ( $offset > 0 ) {
					$statement .= ' OFFSET :offset';
					$params[':offset'] = $offset;
				}
				// Prepare and execute query
				$pdoSt = $this->_sql->prepare( $statement );
				foreach( $params as $ident=>$val ) {
					$pdoSt->bindValue( $ident, (int) $val, PDO::PARAM_INT );
				}
				$pdoSt->execute();
			} else {
				$pdoSt = $this->_sql->query( $statement );
			}
			$pages = array();
			foreach( $pdoSt->fetchAll( PDO::FETCH_ASSOC ) as $row ) {
				$pages[ $row['id'] ] = $row;
			}
			$pdoSt->closeCursor();
			$query = $this->_sql->query( 'SELECT FOUND_ROWS()' );
			$this->pageCount = $query->fetch( PDO::FETCH_COLUMN );
			$query->closeCursor();
			if ( $aclCheck ) {
				foreach( $pages as $tmpPage ) {
					$resource = 'page-view_'.$tmpPage['id'];
					if ( !$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource ) ) {
						unset( $pages[ $tmpPage['id'] ] );
						--$this->pageCount;
					}
				}
			}
			return $pages;
		}

		/**
		 * Gets the number of pages which would have been returned if
		 * Page_Model::getAllPages() had no limit/offset args
		 *
		 * @return int|null
		 */
		public function getCount() {
			$count = $this->pageCount;
			$this->pageCount = null;
			return $count;
		}

		/**
		 * Checks if a page exists by ID or identifier
		 *
		 * @param int|string $page
		 * @param bool $byId
		 * @return bool
		 */
		public function pageExists( $page, $byId=true ) {
			try {
				$this->getPage( $page );
				return true;
			} catch ( Page_NoExist $e ) {
				return false;
			}
		}

		/**
		 * Gets details for a single page, either by ID or identifier
		 *
		 * @param int|string $page
		 * @param bool $byId
		 * @param bool $withBody
		 * @return array
		 */
		public function getPage( $page, $byId=true, $withBody=true ) {
			$cols = $withBody ? '*' : 'id, title, author, date, parent, `order`, identifier';
			$pdoSt = $this->_sql->prepare( 'SELECT '.$cols.' FROM {PREFIX}mod_page
											WHERE '.($byId ? 'id' : 'identifier').' = ?' );
			$pdoSt->execute( array($page) );
			$page = $pdoSt->fetch( PDO::FETCH_ASSOC );
			$pdoSt->closeCursor();
			if ( $page ) {
				return $page;
			} else {
				throw new Page_NoExist( $page );
			}
		}

		/**
		 * Gets all of the children IDs (if any) for a page recursively. Note, this only
		 * gets the ID of the pages!
		 *
		 * An array of PageID's can also be provided which it will ignore for getting
		 * the children for.
		 *
		 * @param int $pid
		 * @param bool $flatArray
		 * @param array $ignore
		 * @param bool $aclCheck
		 * @param bool $withBody
		 * @param int $depth
		 * @return array
		 */
		public function getChildren( $pid, $flatArray=false, array $ignore=array(), $aclCheck=true, $withBody=false, $depth=0 ) {
			$pid = abs( $pid );
			if ( in_array( $pid, $ignore ) || $pid == false ) {
				return array();
			} else {
				$cols = $withBody ? '*' : 'id, title, author, date, parent, `order`, identifier';
				$children = array();
				$query = $this->_sql->query( 'SELECT '.$cols.' FROM {PREFIX}mod_page
											  WHERE parent = '.(int) $pid.' ORDER BY `order`, title ASC' );
				foreach( $query->fetchAll( PDO::FETCH_ASSOC ) as $row ) {
					$resource = 'page-view_'.$row['id'];
					if ( $aclCheck && (!$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource )) ) {
						continue;
					} else {
						$row['depth'] = $depth;
						$tmpChildren = $this->getChildren( $row['id'], $flatArray, $ignore, $aclCheck, $withBody, ($depth+1) );
						if ( $flatArray == false ) {
							$row['children'] = $tmpChildren;
							$children[] = $row;
						} else {
							$children[] = $row;
							$children = array_merge( $children, $tmpChildren );
						}
					}
				}
				return $children;
			}
		}

		/**
		 * Gets the path that was needed to get to a page in the tree. It works
		 * back up the tree, and returns most things (not including body).
		 *
		 * @param int $pid
		 * @return array
		 */
		public function findPath( $pid ) {
			$query = $this->_sql->query( 'SELECT id, parent, title, identifier, author, date FROM {PREFIX}mod_page
										  WHERE id = '.(int) $pid );
			$page = $query->fetch( PDO::FETCH_ASSOC );
			$query->closeCursor();
			if ( $page ) {
				$path = array($page);
				if ( !empty( $page['parent'] ) ) {
					$path = array_merge( $this->findPath( $page['parent'] ), array($page) );
				}
				return $path;
			} else {
				return array();
			}
		}

		/**
		 * Adds a new page and returns ID and identifier if successfuly
		 *
		 * @param string $title
		 * @param string $body
		 * @param int $parent
		 * @return array|bool
		 */
		public function add( $title, $body, $parent=0 ) {
			$editor = new Editor( $body );
			$i = null;
			do {
				try {
					$identifier = zula_clean($title).$i;
					$this->getPage( $identifier, false );
					++$i;
				} catch ( Page_NoExist $e ) {
					break;
				}
			} while( true );
			$pdoSt = $this->_sql->prepare( 'INSERT INTO {PREFIX}mod_page (title, body, author, parent ,date, identifier)
											VALUES(?, ?, ?, ?, UTC_TIMESTAMP(), ?)' );
			$pdoSt->execute( array($title, $editor->preParse(), $this->_session->getUserId(), abs($parent), $identifier) );
			if ( $pdoSt->rowCount() ) {
				$id = $this->_sql->lastInsertId();
				Hooks::notifyAll( 'page_add', $id, $identifier );
				return array(
							'id'			=> $id,
							'identifier'	=> $identifier,
							);
			} else {
				return false;
			}
		}

		/**
		 * Edits an existing page
		 *
		 * @param int $pid
		 * @param string $title
		 * @param string $body
		 * @param int $parent
		 * @return bool
		 */
		public function edit( $pid, $title, $body, $parent ) {
			$page = $this->getPage( $pid );
			$editor = new Editor( $body );
			$pdoSt = $this->_sql->prepare( 'UPDATE {PREFIX}mod_page SET title = ?, body = ?, parent = ? WHERE id = ?' );
			return $pdoSt->execute( array($title, $editor->preParse(), abs($parent), $page['id']) );
		}

		/**
		 * Removes a page and all children under it, by ID
		 *
		 * @param int $pid
		 * @return int
		 */
		public function delete( $pid ) {
			$page = $this->getPage( $pid );
			// Get all of the IDs of pages to delete
			$pageIds = array($page['id']);
			foreach( $this->getChildren($page['id'], true, array(), false) as $child ) {
				$pageIds[] = $child['id'];
			}
			// Remove the needed ACL resources and SQL entries
			$pdoSt = $this->_sql->prepare( 'DELETE FROM {PREFIX}mod_page WHERE id = ?' );
			$aclResources = array();
			$delCount = 0;
			foreach( $pageIds as $id ) {
				$pdoSt->execute( array($id) );
				if ( $pdoSt->rowCount() ) {
					$aclResources[] = 'page-view_'.$id;
					$aclResources[] = 'page-edit_'.$id;
					$aclResources[] = 'page-manage_'.$id;
					++$delCount;
				}
			}
			$pdoSt->closeCursor();
			$this->_acl->deleteResource( $aclResources );
			Hooks::notifyAll( 'page_delete', $delCount, $pageIds );
			return $delCount;
		}

	}

?>
