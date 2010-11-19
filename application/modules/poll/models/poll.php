<?php

/**
 * Zula Framework Module
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Poll
 */

	class Poll_model extends Zula_ModelBase {

		/**
		 * Stores the poll count (found rows without limit for self::getPolls())
		 * @var int|null
		 */
		protected $pollCount = null;

		/**
		 * Gets existing polls from the database, can also limit the result set
		 * returned.
		 *
		 * If set, it will check if user has permission to the poll, if not the
		 * poll will not be returned or counted.
		 *
		 * @param int $limit
		 * @param int $offset
		 * @param bool $aclCheck
		 * @return array
		 */
		public function getAllPolls( $limit=0, $offset=0, $aclCheck=true ) {
			$query = $this->_sql->makeQuery()
					->select( array('id','title','status','start_date','end_date','TIMESTAMPDIFF(second, start_date, end_date)'),
							'{PREFIX}mod_poll')
					->order( array('start_date' => 'DESC') )
					->limit( $offset, $limit == 0 ? 1000000 : $limit );
			$query->build();
			if ( $limit != 0 || $offset != 0 ) {
				// Prepare and execute query
				$pdoSt = $this->_sql->prepare( $query->getSql() );
				foreach( $query->getBoundParams() as $ident=>$val ) {
					$pdoSt->bindValue( $ident, (int) $val, PDO::PARAM_INT );
				}
				$pdoSt->execute( $this->getBoundParams() );
			} else {
				$cacheKey = 'polls'; # Used later on as well
				$polls = $this->_cache->get( $cacheKey );
				if ( $polls == false ) {
					$pdoSt = $this->_sql->prepare( $query->getSql() );
					foreach( $query->getBoundParams() as $ident=>$val ) {
						$pdoSt->bindValue( $ident, (int) $val, PDO::PARAM_INT );
					}
					$pdoSt->execute();
				} else {
					$this->pollCount = count( $polls );
				}
			}
			if ( isset( $pdoSt ) ) {
				$polls = array();
				foreach( $pdoSt->fetchAll( PDO::FETCH_ASSOC ) as $row ) {
					$polls[ $row['id'] ] = $row;
				}
				$pdoSt->closeCursor();
				$query = $this->_sql->query( 'SELECT COUNT(*) FROM {PREFIX}mod_poll' );
				$this->pollCount = $query->fetch( PDO::FETCH_COLUMN );
				$query->closeCursor();
				if ( isset( $cacheKey ) ) {
					$this->_cache->add( $cacheKey, $polls );
				}
			}
			if ( $aclCheck ) {
				foreach( $polls as $tmpPoll ) {
					$resource = 'poll-'.$tmpPoll['id'];
					if ( !$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource ) ) {
						unset( $polls[ $tmpPoll['id'] ] );
						--$this->pollCount;
					}
				}
			}
			return $polls;
		}

		/**
		 * Gets the number of polls which would have been returned if
		 * Poll_Model::getPolls() had no limit/offset args
		 *
		 * @return int|null
		 */
		public function getCount() {
			$count = $this->pollCount;
			$this->pollCount = null;
			return $count;
		}

		/**
		 * Checks if a poll exists
		 *
		 * @param int $pid
		 * @return bool
		 */
		public function pollExists( $pid ) {
			try {
				$this->getPoll( $pid );
				return true;
			} catch ( Poll_NoExist $e ) {
				return false;
			}
		}

		/**
		 * Attempts to get details for a poll
		 *
		 * @param int $pid
		 * @return array
		 */
		public function getPoll( $pid ) {
			if ( $this->_sql->getAttribute( PDO::ATTR_DRIVER_NAME ) == 'sqlsrv' ) {
				$pdoSt = $this->_sql->prepare( 'SELECT id, title, status, start_date, end_date,
												DATEDIFF(second, start_date, end_date) AS duration
											FROM {PREFIX}mod_poll WHERE id = :id' );
			} else {
				$pdoSt = $this->_sql->prepare( 'SELECT id, title, status, start_date, end_date,
												TIMESTAMPDIFF(SECOND, start_date, end_date) AS duration
											FROM {PREFIX}mod_poll WHERE id = :id' );
			}
			$pdoSt->bindValue( ':id', $pid, PDO::PARAM_INT );
			$pdoSt->execute();
			$poll = $pdoSt->fetch( PDO::FETCH_ASSOC );
			$pdoSt->closeCursor();
			if ( $poll ) {
				return $poll;
			} else {
				throw new Poll_NoExist( $pid );
			}
		}

		/**
		 * Gets all options for a specified poll ID
		 *
		 * @param int $pid
		 * @return array
		 */
		public function getPollOptions( $pid ) {
			return $this->_sql->query( 'SELECT * FROM {PREFIX}mod_poll_options
										WHERE poll_id = '.(int) $pid.' ORDER BY id ASC' )
							  ->fetchAll( PDO::FETCH_ASSOC );
		}

		/**
		 * Checks if a poll option exists
		 *
		 * @param int $id
		 * @return bool
		 */
		public function optionExists( $id ) {
			try {
				$this->getOption( $id );
				return true;
			} catch ( Exeception $e ) {
				return false;
			}
		}

		/**
		 * Gets details for a single poll option
		 *
		 * @param int $id
		 * @return array
		 */
		public function getOption( $id ) {
			$query = $this->_sql->query( 'SELECT * FROM {PREFIX}mod_poll_options WHERE id = '.(int) $id );
			$option = $query->fetch( PDO::FETCH_ASSOC );
			$query->closeCursor();
			if ( $option ) {
				return $option;
			} else {
				throw new Poll_OptionNoExist( $id );
			}
		}

		/**
		 * Gets all the votes for a single poll ID
		 *
		 * @param int $pid
		 * @return array
		 */
		public function getPollVotes( $pid ) {
			$query = $this->_sql->query( 'SELECT votes.* FROM {PREFIX}mod_poll_votes AS votes
											LEFT JOIN {PREFIX}mod_poll_options AS options ON options.id = votes.option_id
										  WHERE options.poll_id = '.(int) $pid );
			$votes = array();
			foreach( $query->fetchAll( PDO::FETCH_ASSOC ) as $row ) {
				$votes[ $row['option_id'] ][] = $row;
			}
			return $votes;
		}

		/**
		 * Adds a new poll with specified options
		 *
		 * @param string $title
		 * @param int $duration
		 * @param array $options
		 * @param string $status
		 * @return int
		 */
		public function addPoll( $title, $duration, array $options, $status='active' ) {
			if ( $this->_sql->getAttribute( PDO::ATTR_DRIVER_NAME ) == 'sqlsrv' ) {
				$pdoSt = $this->_sql->prepare( 'INSERT INTO {PREFIX}mod_poll(title, status, start_date, end_date)
									VALUES (?, ?, UTC_TIMESTAMP(), DATEADD(second, ?, UTC_TIMESTAMP()))' );
			} else {
				$pdoSt = $this->_sql->prepare( 'INSERT INTO {PREFIX}mod_poll(title, status, start_date, end_date)
									VALUES (?, ?, UTC_TIMESTAMP(), DATE_ADD(UTC_TIMESTAMP(), INTERVAL ? SECOND))' );

			}
			$pdoSt->execute( array($title, $status, $duration) );
			$this->_cache->delete( 'polls' );
			$pid = $this->_sql->lastInsertId();
			$this->addOption( $pid, $options );
			return $pid;
		}

		/**
		 * Edits a poll with the new details
		 *
		 * @param int $pid
		 * @param string $title
		 * @param int $duration
		 * @param string $status
		 * @return bool
		 */
		public function editPoll( $pid, $title, $duration, $status ) {
			$poll = $this->getPoll( $pid );
			if ( $this->_sql->getAttribute( PDO::ATTR_DRIVER_NAME ) == 'sqlsrv' ) {
				$pdoSt = $this->_sql->prepare( 'UPDATE {PREFIX}mod_poll
								SET title = ?, status = ?, end_date = DATEADD(second, ?, start_date)
								WHERE id = ?' );
			} else {
				$pdoSt = $this->_sql->prepare( 'UPDATE {PREFIX}mod_poll
								SET title = ?, status = ?, end_date = DATE_ADD(start_date, INTERVAL ? SECOND)
								WHERE id = ?' );
			}
			$this->_cache->delete( 'polls' );
			return $pdoSt->execute( array($title, $status, $duration, $pid) );
		}

		/**
		 * Deletes a single specified poll and all poll options + votes.
		 *
		 * @param int $pid
		 * @return bool
		 */
		public function deletePoll( $pid ) {
			$poll = $this->getPoll( $pid );
			$pdoSt = $this->_sql->prepare( 'DELETE options, poll
											FROM {PREFIX}mod_poll AS poll
												INNER JOIN {PREFIX}mod_poll_options AS options
											WHERE poll.id = :pid AND options.poll_id = :pid' );
			$pdoSt->execute( array(':pid' => $poll['id']) );
			$pdoSt->closeCursor();
			if ( $pdoSt->rowCount() > 0 ) {
				$this->_acl->deleteResource( 'poll-'.$poll['id'] );
				$this->_cache->delete( 'polls' );
				$this->removeOrphanVotes();
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Adds one or more option(s) to a specified poll
		 *
		 * @param int $pid
		 * @param string|array $options
		 * @return int
		 */
		public function addOption( $pid, $options ) {
			$poll = $this->getPoll( $pid );
			$pdoSt = $this->_sql->prepare( 'INSERT INTO {PREFIX}mod_poll_options (poll_id, title) VALUES(?, ?)' );
			$count = 0;
			foreach( (array) $options as $title ) {
				$pdoSt->execute( array($pid, $title) );
				$count += $pdoSt->rowCount();
			}
			return $count;
		}

		/**
		 * Edits a single poll options title
		 *
		 * @param int $id
		 * @param string $title
		 * @return bool
		 */
		public function editOption( $id, $title ) {
			$option = $this->getOption( $id );
			$pdoSt = $this->_sql->prepare( 'UPDATE {PREFIX}mod_poll_options SET title = ? WHERE id = ?' );
			$pdoSt->execute( array($title, $option['id']) );
			return (bool) $pdoSt->rowCount();
		}

		/**
		 * Deletes a poll option and all votes under it
		 *
		 * @param int $id
		 * @return bool
		 */
		public function deleteOption( $id ) {
			$option = $this->getOption( $id );
			$pdoSt = $this->_sql->prepare( 'DELETE FROM {PREFIX}mod_poll_options WHERE id = ?' );
			$pdoSt->execute( array($option['id']) );
			$this->removeOrphanVotes();
			return true;
		}

		/**
		 * Removes all orphaned votes
		 *
		 * @return int
		 */
		protected function removeOrphanVotes() {
			$query = $this->_sql->query( 'DELETE votes
										  FROM {PREFIX}mod_poll_votes AS votes
											LEFT JOIN {PREFIX}mod_poll_options AS options ON votes.option_id = options.id
										  WHERE options.id IS NULL' );
			$query->closeCursor();
			return $query->rowCount();
		}

		/**
		 * Adds a new vote to a poll option
		 *
		 * @param int $oid
		 * @return bool
		 */
		public function vote( $oid ) {
			$option = $this->getOption( $oid );
			$pdoSt = $this->_sql->prepare( 'INSERT INTO {PREFIX}mod_poll_votes (option_id, ip, uid) VALUES(?, ?, ?)' );
			return $pdoSt->execute( array(
										$option['id'],
										zula_ip2long( zula_get_client_ip() ),
										$this->_session->getUserId()
										)
								   );
		}

		/**
		 * Updates the status of the poll to be closed
		 *
		 * @param int $pid
		 * @return bool
		 */
		public function closePoll( $pid ) {
			$poll = $this->getPoll( $pid );
			$pdoSt = $this->_sql->prepare( 'UPDATE {PREFIX}mod_poll
											SET status = "closed" WHERE id = :id AND status = "active"' );
			$pdoSt->bindValue( ':id', $poll['id'], PDO::PARAM_INT );
			$pdoSt->execute();
			$pdoSt->closeCursor();
			return (bool) $pdoSt->rowCount();
		}

	}

?>
