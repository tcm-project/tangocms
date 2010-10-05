<?php

/**
 * Zula Framework Model
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Media
 */

	class Media_model extends Zula_ModelBase {

		/**
		 * Stores how many categories would have been returned, if not limit/offset
		 * @var int|bool
		 */
		protected $categoryCount = false;

		/**
		 * Shows how many media items would have been returned, if no limit/offset
		 * @var int|bool
		 */
		protected $itemCount = false;

		/**
		 * Gets all categories, can also limit the result set and check
		 * user ACL permissions
		 *
		 * @param int $limit
		 * @param int $offset
		 * @param bool $aclCheck
		 * @return array
		 */
		public function getAllCategories( $limit=0, $offset=0, $aclCheck=true ) {
			$statement = 'SELECT SQL_CALC_FOUND_ROWS mcats.*, COUNT(mitems.id) AS item_count
						  FROM
							{SQL_PREFIX}mod_media_cats mcats
							LEFT JOIN {SQL_PREFIX}mod_media_items mitems ON mitems.cat_id = mcats.id
						  GROUP BY mcats.id';
			if ( $limit != 0 || $offset != 0 ) {
				if ( $limit > 0 ) {
					$statement .= ' LIMIT '.(int) $limit;
				} else if ( empty( $limit ) && !empty( $offset ) ) {
					$statement .= ' LIMIT 1000000';
				}
				if ( $offset > 0 ) {
					$statement .= ' OFFSET '.(int) $offset;
				}
				$query = $this->_sql->query( $statement );
			} else {
				// Get from cache instead, if possible
				$cacheKey = 'media_cats';
				if ( !($categories = $this->_cache->get($cacheKey)) ) {
					$statement .= ' ORDER BY mcats.name ASC';
					$query = $this->_sql->query( $statement );
				}
			}
			if ( isset( $query ) ) {
				$categories = array();
				foreach( $query->fetchAll( PDO::FETCH_ASSOC ) as $row ) {
					$categories[ $row['id'] ] = $row;
				}
				$query->closeCursor();
				if ( isset( $cacheKey ) ) {
					$this->_cache->add( $cacheKey, $categories );
				}
				// Get how many results there would have been
				$this->categoryCount = $this->_sql->query( 'SELECT FOUND_ROWS()' )
												  ->fetch( PDO::FETCH_COLUMN );
			} else {
				$this->categoryCount = count( $categories );
			}
			if ( $aclCheck ) {
				foreach( $categories as $key=>$cat ) {
					$resource = 'media-cat_view_'.$cat['id'];
					if ( !$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource ) ) {
						unset( $categories[ $key ] );
					}
				}
			}
			return $categories;
		}

		/**
		 * Gets how many true results would have been returned if
		 * self::getAllCategories() has no limit/offset
		 *
		 * @return int|bool
		 */
		public function getCategoryCount() {
			$count = $this->categoryCount;
			$this->categoryCount = false;
			return $count;
		}

		/**
		 * Checks if a category exists
		 *
		 * @param int|string $cat
		 * @param bool $byId
		 * @return bool
		 */
		public function categoryExists( $cat, $byId=true ) {
			try {
				$this->getCategory( $cat, $byId );
				return true;
			} catch ( Media_CategoryNoExist $e ) {
				return false;
			}
		}

		/**
		 * Gets details for a single category by ID or clean name
		 *
		 * @param int|string $cat
		 * @param bool $byId
		 * @return array
		 */
		public function getCategory( $cat, $byId=true ) {
			$pdoSt = $this->_sql->prepare( 'SELECT mcats.*, COUNT(mitems.id) AS item_count
											FROM
												{SQL_PREFIX}mod_media_cats mcats
												LEFT JOIN {SQL_PREFIX}mod_media_items mitems ON mitems.cat_id = mcats.id
											WHERE mcats.'.($byId ? 'id' : 'clean_name').' = ?
											GROUP BY mcats.id' );
			$pdoSt->execute( array($cat) );
			$category = $pdoSt->fetch( PDO::FETCH_ASSOC );
			$pdoSt->closeCursor();
			if ( $category['id'] !== null ) {
				return $category;
			} else {
				throw new Media_CategoryNoExist( $cat );
			}
		}

		/**
		 * Gets all media items for a category (or no category) and can limit
		 * the result set. If no category is selected, it can check ACL permissions
		 * on the parent category as well.
		 *
		 * @param int $limit
		 * @param int $offset
		 * @param mixed $cid
		 * @param bool $aclCheck
		 * @return array
		 */
		public function getItems( $limit=0, $offset=0, $cid=null, $aclCheck=true ) {
			$statement = 'SELECT SQL_CALC_FOUND_ROWS *
						  FROM {SQL_PREFIX}mod_media_items WHERE outstanding = 0';
			$params = array();
			if ( $cid ) {
				$statement .= ' AND cat_id = :cid';
				$params[':cid'] = $cid;
			}
			if ( $limit != 0 || $offset != 0 ) {
				$statement .= ' ORDER BY date DESC';
				if ( $limit > 0 ) {
					$statement .= ' LIMIT :limit';
					$params[':limit'] = $limit;
				} else if ( empty( $limit ) && !empty( $offset ) ) {
					$statement .= ' LIMIT 1000000';
				}
				if ( $offset > 0 ) {
					$statement .= ' OFFSET :offset';
					$params[':offset'] = $offset;
				}
			} else {
				$statement .= ' ORDER BY date DESC';
			}
			$pdoSt = $this->_sql->prepare( $statement );
			foreach( $params as $key=>$val ) {
				$pdoSt->bindValue( $key, (int) $val, PDO::PARAM_INT );
			}
			$pdoSt->execute();
			$items = array();
			foreach( $pdoSt->fetchAll( PDO::FETCH_ASSOC ) as $row ) {
				if ( !$cid && $aclCheck ) {
					// Check if user has permission to parent category
					$resource = 'media-cat_view_'.$row['cat_id'];
					if ( !$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource ) ) {
						continue; # Don't return this result
					}
				}
				$items[ $row['id'] ] = $row;
			}
			// Get the real amount of rows that would have been returned
			$pdoSt->closeCursor();
			$this->itemCount = $this->_sql->query( 'SELECT FOUND_ROWS()' )
										   ->fetch( PDO::FETCH_COLUMN );
			return $items;
		}

		/**
		 * Gets how many media items would have been returned if
		 * self::getItems() had no offset/limit
		 *
		 * @return int|bool
		 */
		public function getItemCount() {
			$count = $this->itemCount;
			$this->itemCount = false;
			return $count;
		}

		/**
		 * Gets details for an item by ID or clean name
		 *
		 * @param string|int $item
		 * @param bool $byId
		 * @return array
		 */
		public function getItem( $item, $byId=true ) {
			$pdoSt = $this->_sql->prepare( 'SELECT * FROM {SQL_PREFIX}mod_media_items WHERE '.($byId ? 'id' : 'clean_name').' = ?' );
			$pdoSt->execute( array($item) );
			$details = $pdoSt->fetch( PDO::FETCH_ASSOC );
			$pdoSt->closeCursor();
			if ( $details ) {
				// Add in the filesystem path
				$details['path_fs'] = $this->_zula->getDir( 'uploads' ).'/media/'.$details['cat_id'].'/'.$details['type'];
				return $details;
			} else {
				throw new Media_ItemNoExist( $item );
			}
		}

		/**
		 * Gets all outstanding media items, ordered by date. Can limit to a
		 * specified category if needed. If set to, ACL permissions will be
		 * checked on the parent category.
		 *
		 * @param int $cid
		 * @param bool $aclCheck
		 * @return array
		 */
		public function getOutstandingItems( $cid=null, $aclCheck=true ) {
			$statement = 'SELECT * FROM {SQL_PREFIX}mod_media_items WHERE outstanding = 1';
			if ( $cid ) {
				$statement .= ' AND cat_id = '.(int) $cid;
			}
			$items = array();
			foreach( $this->_sql->query( $statement, PDO::FETCH_ASSOC ) as $row ) {
				$resource = 'media-cat_upload_'.$row['cat_id'];
				if ( !$aclCheck || ($aclCheck && $this->_acl->resourceExists($resource) && $this->_acl->check($resource)) ) {
					$items[ $row['id'] ] = $row;
				}
			}
			return $items;
		}

		/**
		 * Adds a new category and returns the ID
		 *
		 * @param string $name
		 * @param string $desc
		 * @return int
		 */
		public function addCategory( $name, $desc='' ) {
			$i = null;
			do {
				try {
					$cleanName = zula_clean( $name ).$i++;
					$this->getCategory( $cleanName, false );
				} catch ( Media_CategoryNoExist $e ) {
					break;
				}
			} while ( true );
			// Insert new category
			$pdoSt = $this->_sql->prepare( 'INSERT INTO {SQL_PREFIX}mod_media_cats (name, description, clean_name) VALUES(?, ?, ?)' );
			$pdoSt->execute( array($name, $desc, $cleanName) );
			$this->_cache->delete( 'media_cats' );
			return $this->_sql->lastInsertId();
		}

		/**
		 * Edits an existing category
		 *
		 * @param int $id
		 * @param string $name
		 * @param string $desc
		 * @return bool
		 */
		public function editCategory( $id, $name, $desc ) {
			$category = $this->getCategory( $id );
			$pdoSt = $this->_sql->prepare( 'UPDATE {SQL_PREFIX}mod_media_cats
											SET name = ?, description = ? WHERE id = ?' );
			$this->_cache->delete( 'media_cats' );
			return $pdoSt->execute( array($name, $desc, $id) );
		}

		/**
		 * Deletes a media category and all media items under it. It does
		 * not delete the files though. Returns how many media items were
		 * deleted, or false on failure.
		 *
		 * @param int $id
		 * @return int|bool
		 */
		public function deleteCategory( $id ) {
			$category = $this->getCategory( $id );
			$query = $this->_sql->query( 'DELETE FROM {SQL_PREFIX}mod_media_cats WHERE id = '.(int) $category['id'] );
			if ( $query->rowCount() ) {
				$query->closeCursor();
				$this->_cache->delete( 'media_cats' );
				// Remove ACL resources
				$cid = $category['id'];
				$this->_acl->deleteResource( array('media-cat_view_'.$cid, 'media-cat_upload_'.$cid, 'media-cat_moderate_'.$cid) );
				// Delete all media items
				$query = $this->_sql->query( 'DELETE FROM {SQL_PREFIX}mod_media_items WHERE cat_id = '.(int) $cid );
				return $query->rowCount();
			} else {
				return false;
			}
		}

		/**
		 * Purges a media category/removes all media items. This will not remove
		 * any files, just the database entries. Returns number of media items deleted
		 *
		 * @param int $id
		 * @return int
		 */
		public function purgeCategory( $id ) {
			$category = $this->getCategory( $id );
			$query = $this->_sql->query( 'DELETE FROM {SQL_PREFIX}mod_media_items WHERE cat_id = '.(int) $category['id'] );
			return $query->rowCount();
		}

		/**
		 * Adds a new media item to a category
		 *
		 * @param int $cid
		 * @param string $name
		 * @param string $desc
		 * @param string $type		Video/Image/Audio/External
		 * @param string $filename
		 * @param string $thumbnail
		 * @param string $externalService
		 * @param string $externalId
		 * @return array
		 */
		public function addItem( $cid, $name, $desc, $type, $filename, $thumbnail, $externalService='', $externalId='' ) {
			$category = $this->getCategory( $cid );
			// Create the clean name for the item
			$i = null;
			do {
				try {
					$cleanName = zula_clean( $name ).$i;
					$this->getItem( $cleanName, false );
					++$i;
				} catch ( Media_ItemNoExist $e ) {
					break;;
				}
			} while( true );
			// Insert the new media item
			$pdoSt = $this->_sql->prepare( 'INSERT INTO {SQL_PREFIX}mod_media_items
											(cat_id, type, date, name, clean_name, description, filename, thumbnail, external_service, external_id)
											VALUES(?, ?, UTC_TIMESTAMP(), ?, ?, ?, ?, ?, ?, ?)' );
			$pdoSt->execute( array($category['id'], $type, $name, $cleanName, $desc, $filename, $thumbnail, $externalService, $externalId) );
			return array(
						'id'			=> $this->_sql->lastInsertId(),
						'clean_name'	=> $cleanName,
						);
		}

		/**
		 * Edits a media item
		 *
		 * @param int $id
		 * @param string $name
		 * @param string $desc
		 * @return bool
		 */
		public function editItem( $id, $name, $desc ) {
			$item = $this->getItem( $id );
			$pdoSt = $this->_sql->prepare( 'UPDATE {SQL_PREFIX}mod_media_items SET name = ?, description = ?, outstanding = 0 WHERE id = ?' );
			return $pdoSt->execute( array($name, $desc, $id) );
		}

		/**
		 * Deletes a a media item, but not the files.
		 *
		 * @param int $id
		 * @return bool
		 */
		public function deleteItem( $id ) {
			$item = $this->getItem( $id );
			$query = $this->_sql->query( 'DELETE FROM {SQL_PREFIX}mod_media_items WHERE id = '.(int) $item['id'] );
			return (bool) $query->rowCount();
		}

	}

?>
