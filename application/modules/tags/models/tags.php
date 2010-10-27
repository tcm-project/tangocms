<?php

/**
 * Zula Framework Model (Tags)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Robert Clipsham
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009, Robert Clipsham
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Tags
 */

	class Tags_model extends Zula_ModelBase {

		const
			_ID		= 0, # Get tag from tag ID
			_URL	= 1; # Get tag from URL

		/**
		 * All tags that are in the DB
		 * @var array
		 */
		protected $tags = array();

		/**
		 * Get a list of all tags
		 *
		 * @return array
		 */
		public function getAll() {
			if ( empty( $this->tags ) ) {
				$query = $this->_sql->query( 'SELECT LOWER(name) AS name, id FROM {PREFIX}mod_tags' );
				foreach( $query->fetchAll( PDO::FETCH_ASSOC ) as $row ) {
					$this->tags[ $row['id'] ] = $row['name'];
				}
			}
			return $this->tags;
		}

		/**
		 * Get all the tags that match the given id or name
		 *
		 * @param string $id
		 * @param int $type
		 * @return array
		 */
		public function getTags( $id, $type=self::_URL ) {
			if ( $type == self::_ID ) {
				$query = 'SELECT name FROM {PREFIX}mod_tags WHERE id = ?';
			} else {
				// Get all tags for a given URL
				$query = 'SELECT tags.name
						  FROM {PREFIX}mod_tags AS tags
							LEFT JOIN {PREFIX}mod_tags_xref AS xref ON xref.tag = tags.id
						  WHERE xref.url = ?';
			}
			$pdoSt = $this->_sql->prepare( $query );
			$pdoSt->execute( array($id) );
			return $pdoSt->fetchAll( PDO::FETCH_COLUMN );
		}

		/**
		 * Gets all URLs that are tagged with a given tag
		 *
		 * @param string $tag
		 * @return array
		 */
		public function getUrls( $tag ) {
			$pdoSt = $this->_sql->prepare( 'SELECT xref.url
											FROM {PREFIX}mod_tags_xref AS xref
												LEFT JOIN {PREFIX}mod_tags AS tags ON tags.id = xref.tag
											WHERE tags.name = ?' );
			$pdoSt->execute( array($tag) );
			return $pdoSt->fetchAll( PDO::FETCH_COLUMN );
		}

		/**
		 * Get tag statistics, used for tag cloud
		 *
		 * @return array
		 */
		public function getStats() {
			if ( !$stats = $this->_cache->get( 'tags_stats' ) ) {
				$stats = array();
				foreach( $this->_sql->query( 'SELECT tag FROM {PREFIX}mod_tags_xref' )->fetchAll( PDO::FETCH_COLUMN ) as $tagId ) {
					$tag = $this->getTags( $tagId, self::_ID );
					if ( isset( $tag[0] ) ) {
						if ( isset( $stats[ $tag[0] ] ) ) {
							$stats[ $tag[0] ]++;
						} else {
							$stats[ $tag[0] ] = 1;
						}
					}
				}
				$this->_cache->add( 'tags_stats', $stats );
			}
			return $stats;
		}

		/**
		 * Add a tag with the given name
		 *
		 * @param string $tagName
		 * @return bool|int Id of the tag
		 */
		public function addTag( $tagName ) {
			$this->getAll();
			if ( in_array( $tagName, $this->tags ) ) {
				return array_search( $tagName, $this->tags );
			}
			$pdoSt = $this->_sql->prepare( 'INSERT INTO {PREFIX}mod_tags (name) VALUES(?)' );
			$pdoSt->execute( array($tagName) );
			if ( $pdoSt->rowCount() ) {
				$this->tags[] = $tagName;
				return $this->_sql->lastInsertId();
			} else {
				return false;
			}
		}

		/**
		 * Add a tag to the given URL
		 *
		 * @param string $url
		 * @param int $tagId
		 * @return bool
		 */
		public function addUrl( $url, $tagId ) {
			if ( !$this->urlHasTag( $url, $tagId ) ) {
				$pdoSt = $this->_sql->prepare( 'INSERT INTO {PREFIX}mod_tags_xref (url, tag) VALUES(?, ?)' );
				$pdoSt->execute( array($url, $tagId) );
				if ( $pdoSt->rowCount() ) {
					$this->_cache->delete( 'tags_stats' );
					return true;
				}
			}
			return false;
		}

		/**
		 * Set the tags for a URL
		 *
		 * @param string $url
		 * @param string $tags - A comma seperated list of tags
		 */
		public function setTags( $url, $tags ) {
			$tags = array_unique( array_map('trim', explode(',', $tags)) );
			// Get an array of tagIds
			$this->getAll();
			$tagIds = array();
			foreach( $tags as $tag ) {
				if ( $this->tagExists( $tag ) ) {
					$tagIds[] = array_search( $tag, $this->tags );
				} else {
					$tagIds[] = $this->addTag( $tag );
				}
			}
			$this->delUrlTags( $url ); # So that tags that have been removed aren't left lying around
			foreach( $tagIds as $tagId ) {
				$this->addUrl( $url, $tagId );
			}
		}

		/**
		 * Check if the given URL has a tag associated with it
		 *
		 * @param string $url
		 * @param int $tagId
		 * @return bool
		 */
		public function urlHasTag( $url, $tagId ) {
			$pdoSt = $this->_sql->prepare( 'SELECT COUNT(id) from {PREFIX}mod_tags_xref WHERE url = ? AND tag = ?' );
			$pdoSt->execute( array($url, $tagId) );
			return (bool) $pdoSt->fetch( PDO::FETCH_COLUMN );
		}

		/**
		 * Delete all the tags associated with a URL
		 *
		 * @param string $url
		 * @return bool
		 */
		public function delUrlTags( $url ) {
			$pdoSt = $this->_sql->prepare( 'DELETE FROM {PREFIX}mod_tags_xref WHERE url = ?' );
			$pdoSt->execute( array($url) );
			return $pdoSt->rowCount();
		}

		/**
		 * Check if the given tag exists
		 *
		 * @param string $tagName
		 * @return bool
		 */
		public function tagExists( $tagName ) {
			return in_array( zula_strtolower( $tagName ), $this->getAll() );
		}

	}

?>
