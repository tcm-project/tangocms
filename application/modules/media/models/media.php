<?php

/**
 * Zula Framework Module
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
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
			$query = $this->_sql->makeQuery()
						->select( '*', '{PREFIX}mod_media_cats' )
						->limit( $offset, $limit == 0 ? 1000000 : $limit );
			if ( $limit != 0 || $offset != 0 ) {
				$result = $query->build();
				$query = $this->_sql->prepare( $result[0] );
				$query->execute( $result[1] );

			} else {
				// Get from cache instead, if possible
				$cacheKey = 'media_cats';
				if ( !($categories = $this->_cache->get($cacheKey)) ) {
					$query->order( 'name', 'ASC' );
					$result = $query->build();
					$query = $this->_sql->prepare( $result[0] );
					$query->execute( $result[1] );
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
				$query = $this->_sql->query( 'SELECT COUNT(*) FROM {PREFIX}mod_media_cats' );
				$this->categoryCount = $query->fetch( PDO::FETCH_COLUMN );
				$query->closeCursor();
			} else {
				$this->categoryCount = count( $categories );
			}
			/**
			 * Get the media item count for the categories, and check
			 * ACL resource permission
			 */
			$pdoSt = $this->_sql->prepare( 'SELECT COUNT(*) qty, outstanding
											FROM {PREFIX}mod_media_items
											WHERE cat_id = :cid
											GROUP BY outstanding HAVING qty > 0' );
			foreach( $categories as $key=>$cat ) {
				if ( $aclCheck && !$this->_acl->check('media-cat_view_'.$cat['id']) ) {
					unset( $categories[ $key ] );
					continue;
				}
				$categories[ $key ]['item_count'] = 0;
				$categories[ $key ]['outstanding_count'] = 0;
				$pdoSt->bindValue( ':cid', $cat['id'], PDO::PARAM_INT );
				$pdoSt->execute();
				foreach( $pdoSt->fetchAll( PDO::FETCH_ASSOC ) as $row ) {
					if ( $row['outstanding'] ) {
						$categories[ $key ]['outstanding_count'] += $row['qty'];
					} else {
						$categories[ $key ]['item_count'] += $row['qty'];
					}
				}
			}
			$pdoSt->closeCursor();
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
		 * Gets details for a single category by ID or identifier
		 *
		 * @param int|string $cat
		 * @param bool $byId
		 * @return array
		 */
		public function getCategory( $cat, $byId=true ) {
			$pdoSt = $this->_sql->prepare( 'SELECT mcats.*, COUNT(mitems.id) AS item_count
											FROM
												{PREFIX}mod_media_cats mcats
												LEFT JOIN {PREFIX}mod_media_items mitems ON mitems.cat_id = mcats.id
											WHERE mcats.'.($byId ? 'id' : 'identifier').' = ?
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
			$query = $this->_sql->makeQuery()
						->select( '*', '{PREFIX}mod_media_items' )
						->where( 'outstanding = 0' );
			if ( $cid ) {
				$query->where( 'cat_id = ?', array( $cid ) );
			}
			$query->limit( $offset, $limit == 0 ? 1000000 : $limit )
				->order( array('date' => 'DESC') );
			$result = $query->build();
			$pdoSt = $this->_sql->prepare( $result[0] );
			foreach( $result[1] as $key=>$val ) {
				$pdoSt->bindValue( $key, (int) $val, PDO::PARAM_INT );
			}
			$pdoSt->execute();
			$items = array();
			foreach( $pdoSt->fetchAll( PDO::FETCH_ASSOC ) as $row ) {
				$items[ $row['id'] ] = $row;
			}
			$pdoSt->closeCursor();
			// Find out the total amount of rows
			$statement = 'SELECT COUNT(*) FROM {PREFIX}mod_media_items
							WHERE outstanding = 0';
			if ( $cid ) {
				$statement .= ' AND cat_id = :cid';
			}
			$pdoSt = $this->_sql->prepare( $statement );
			$pdoSt->bindValue( ':cid', $cid, PDO::PARAM_INT );
			$pdoSt->execute();
			$this->itemCount = $pdoSt->fetch( PDO::FETCH_COLUMN );
			$pdoSt->closeCursor();
			// Check ACL resources if needed
			if ( $aclCheck ) {
				foreach( $items as $item ) {
					if ( !$this->_acl->check( 'media-cat_view_'.$item['cat_id'] ) ) {
						--$this->itemCount;

					}
				}
			}
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
		 * Gets details for an item by ID or identifier
		 *
		 * @param string|int $item
		 * @param bool $byId
		 * @return array
		 */
		public function getItem( $item, $byId=true ) {
			$pdoSt = $this->_sql->prepare( 'SELECT * FROM {PREFIX}mod_media_items WHERE '.($byId ? 'id' : 'identifier').' = ?' );
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
			$statement = 'SELECT * FROM {PREFIX}mod_media_items WHERE outstanding = 1';
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
					$identifier = zula_clean( $name ).$i++;
					$this->getCategory( $identifier, false );
				} catch ( Media_CategoryNoExist $e ) {
					break;
				}
			} while ( true );
			// Insert new category
			$pdoSt = $this->_sql->prepare( 'INSERT INTO {PREFIX}mod_media_cats (name, description, identifier) VALUES(?, ?, ?)' );
			$pdoSt->execute( array($name, $desc, $identifier) );
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
			$pdoSt = $this->_sql->prepare( 'UPDATE {PREFIX}mod_media_cats
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
			$query = $this->_sql->query( 'DELETE FROM {PREFIX}mod_media_cats WHERE id = '.(int) $category['id'] );
			if ( $query->rowCount() ) {
				$query->closeCursor();
				$this->_cache->delete( 'media_cats' );
				// Remove ACL resources
				$cid = $category['id'];
				$this->_acl->deleteResource( array('media-cat_view_'.$cid, 'media-cat_upload_'.$cid, 'media-cat_moderate_'.$cid) );
				// Delete all media items
				$query = $this->_sql->query( 'DELETE FROM {PREFIX}mod_media_items WHERE cat_id = '.(int) $cid );
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
			$query = $this->_sql->query( 'DELETE FROM {PREFIX}mod_media_items WHERE cat_id = '.(int) $category['id'] );
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
			// Create the identifier for the item
			$i = null;
			do {
				try {
					$identifier = zula_clean( $name ).$i;
					$this->getItem( $identifier, false );
					++$i;
				} catch ( Media_ItemNoExist $e ) {
					break;;
				}
			} while( true );
			// Insert the new media item
			$pdoSt = $this->_sql->prepare( 'INSERT INTO {PREFIX}mod_media_items
											(cat_id, type, date, name, identifier, description, filename, thumbnail, external_service, external_id)
											VALUES(?, ?, UTC_TIMESTAMP(), ?, ?, ?, ?, ?, ?, ?)' );
			$pdoSt->execute( array($category['id'], $type, $name, $identifier, $desc, $filename, $thumbnail, $externalService, $externalId) );
			return array(
						'id'			=> $this->_sql->lastInsertId(),
						'identifier'	=> $identifier,
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
			$pdoSt = $this->_sql->prepare( 'UPDATE {PREFIX}mod_media_items SET name = ?, description = ?, outstanding = 0 WHERE id = ?' );
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
			$query = $this->_sql->query( 'DELETE FROM {PREFIX}mod_media_items WHERE id = '.(int) $item['id'] );
			return (bool) $query->rowCount();
		}

	}

?>
