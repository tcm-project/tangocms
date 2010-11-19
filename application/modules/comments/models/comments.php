<?php

/**
 * Zula Framework Module
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Comments
 */

 	class Comments_model extends Zula_ModelBase {

		/**
		 * Constants used to control comments by status
		 */
		const
				_ACCEPTED	= 'accepted',
				_MODERATION	= 'moderation',
				_ALL		= 'all';

		/**
		 * Stores the comment count (found rows without limit)
		 * @var int|bool
		 */
		protected $commentCount = false;

		/**
		 * Gets all comments, or for a specific request path. Can also
		 * restrict the result set returned.
		 *
		 * @param string $requestPath
		 * @param string $status
		 * @param int $limit
		 * @param int $offset
		 * @param string $order
		 * @return array
		 */
		public function get( $requestPath=null, $status=self::_ACCEPTED, $limit=0, $offset=0, $order='ASC' ) {
			$query = $this->_sql->makeQuery()->select( '*', '{PREFIX}mod_comments' );
			if ( $requestPath !== null ) {
				$query->where( 'url LIKE ?', array( $requestPath.'%' ) )
			}
			if ( $status != self::_ALL ) {
				$query->where( 'status = ?', array( $status ) );
			}
			if ( strtoupper( $order ) != 'ASC' ) {
				$order = 'DESC';
			}
			$query->limit( $offset, $limit == 0 ? 1000000 : $limit );
			$query->order( array('date' => $order) );
			$result = $query->build();

			// Check if we need to limit the result set
			if ( $limit != 0 || $offset != 0 ) {
				$comments = array();
				$query->order( array('date' => $order) );

				// Prepare and execute query
				$pdoSt = $this->_sql->prepare( $query->getSql() );

				foreach( $query->getBoundParams() as $ident=>$val ) {
					if ( is_string($val) ) {
						$pdoSt->bindValue( $ident, $val );
					} else {
						$pdoSt->bindValue( $ident, (int) $val, PDO::PARAM_INT );
					}
				}
				$pdoSt->execute();
			} else {
				/**
				 * Get all comments of a certain status. Only cache accepted comments
				 */
				if ( $status == self::_ACCEPTED ) {
					$cacheKey = 'comments';
					if ( $requestPath !== null ) {
						$cacheKey .= '_'.zula_hash( $requestPath );
					}
				} else {
					$cacheKey = null;
				}
				if ( !($comments = $this->_cache->get( $cacheKey )) ) {
					$comments = array();
					$pdoSt = $this->_sql->prepare( $result[0] );
					$pdoSt->execute( $result[1] );
				}
			}
			if ( isset( $pdoSt ) ) {
				foreach( $pdoSt->fetchAll( PDO::FETCH_ASSOC ) as $row ) {
					$editor = new Editor( "#!plaintext\n".$row['body'], array('nofollow' => true) );
					$row['body'] = $editor->parse( false, true );
					$comments[ $row['id'] ] = $row;
				}
				if ( isset( $cacheKey ) ) {
					$this->_cache->add( $cacheKey, $comments );
				}
				$pdoSt->closeCursor();
				// Find out the number of rows without the limits
				$statement = 'SELECT COUNT(*) FROM {PREFIX}mod_comments';
				if ( $where != 'WHERE' ) {
					$statement .= ' '.$where;
				}
				$pdoSt = $this->_sql->prepare( $statement );
				foreach( $params as $ident=>$val ) {
					if ( $ident == ':url' || $ident == ':status' ) {
						$pdoSt->bindValue( $ident, $val );
					} else {
						$pdoSt->bindValue( $ident, (int) $val, PDO::PARAM_INT );
					}
				}
				$pdoSt->execute();
				$this->commentCount = $pdoSt->fetch( PDO::FETCH_COLUMN );
				$pdoSt->closeCursor();
			} else {
				$this->commentCount = count( $comments );
			}
			return $comments;
		}

		/**
		 * Gets the number of comments which would have been returned if
		 * Comments_Model::get() had no limit/offset args
		 *
		 * @return int|bool
		 */
		public function getCount() {
			$count = $this->commentCount;
			$this->commentCount = false;
			return $count;
		}

		/**
		 * Gets details for a single comment
		 *
		 * @param int $id
		 * @return array
		 */
		public function getDetails( $id ) {
			$pdoSt = $this->_sql->prepare( 'SELECT * FROM {PREFIX}mod_comments WHERE id = ?' );
			$pdoSt->execute( array($id) );
			if ( ($details = $pdoSt->fetch( PDO::FETCH_ASSOC )) ) {
				$pdoSt->closeCursor();
				return $details;
			}
			throw new Comments_NoExist( 'comment id "'.$id.'" does not exist' );
		}

		/**
		 * Adds a new comment for a given request path
		 *
		 * @param string $requestPath
		 * @param string $body
		 * @param string $name
		 * @param string $website
		 * @return int
		 */
		public function add( $requestPath, $body, $name='', $website='' ) {
			$status = $this->_config->get('comments/moderate') ? 'moderation' : 'accepted';
			$pdoSt = $this->_sql->prepare( 'INSERT INTO {PREFIX}mod_comments
											(user_id, status, url, date, body, name, website) VALUES (?, ?, ?, UTC_TIMESTAMP(), ?, ?, ?)' );
			$execData = array($this->_session->getUserId(), $status, $requestPath, $body, $name, $website);
			if ( !$pdoSt->execute( $execData ) ) {
				throw new Comments_Exception( 'failed to add new comment, execute failed' );
			}
			$this->_cache->delete( 'comments_'.zula_hash( $requestPath ) );
			return $this->_sql->lastInsertId();
		}

		/**
		 * Deletes comments by ID
		 *
		 * @return bool
		 */
		public function delete( $comments ) {
			if ( !is_array( $comments ) ) {
				$comments = array($comments);
			}
			// Gather all request paths/urls so we can clear cache ... a pain I know
			$queryIn = rtrim( str_repeat( '?, ', count($comments) ), ', ' );
			$pdoSt = $this->_sql->prepare( 'SELECT url FROM {PREFIX}mod_comments WHERE id IN ( '.$queryIn.' )' );
			$pdoSt->execute( $comments );
			foreach( $pdoSt->fetchAll( PDO::FETCH_COLUMN ) as $path ) {
				$this->_cache->delete( 'comments_'.zula_hash( $path ) );
			}
			$this->_cache->delete( 'comments' );
			$pdoSt->closeCursor();
			// Now delete
			return $this->_sql->prepare( 'DELETE FROM {PREFIX}mod_comments WHERE id IN ( '.$queryIn.' )' )
							  ->execute( $comments );
		}

		/**
		 * Edits certain details about a comment
		 *
		 * @param int $commentId
		 * @param array $details
		 * @return bool
		 */
		public function edit( $commentId, array $details ) {
			$commentDetails = $this->getDetails( $commentId );
			$query = 'UPDATE {PREFIX}mod_comments SET ';
			$execData = array();
			foreach( $details as $key=>$val ) {
				$query .= $key.' = ?, ';
				$execData[] = $val;
			}
			$query = rtrim( $query, ', ' ).' WHERE id = ?';
			$execData[] = $commentId;
			if ( $this->_sql->prepare( $query )->execute( $execData ) ) {
				$this->_cache->delete( 'comments_'.zula_hash($commentDetails['url']) );
				$this->_cache->delete( 'comments' );
				return true;
			} else {
				return false;
			}
		}

 	}

?>
