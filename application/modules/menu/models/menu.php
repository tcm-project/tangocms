<?php
// $Id: menu.php 2768 2009-11-13 18:12:34Z alexc $

/**
 * Zula Framework Model (menu model)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Menu
 */

	class Menu_model extends Zula_ModelBase {

		/**
		 * Returns every menu category there is. Either from SQL
		 * or the stored array
		 *
		 * @param bool $aclCheck Check category permissions
		 * @return array
		 */
		public function getAllCategories( $aclCheck=true ) {
			if ( !($categories = $this->_cache->get('menu_categories')) ) {
				$categories = array();
				foreach( $this->_sql->query( 'SELECT * FROM {SQL_PREFIX}mod_menu_cats', PDO::FETCH_ASSOC ) as $category ) {
					$categories[ $category['id'] ] = $category;
				}
				$this->_cache->add( 'menu_categories', $categories );
			}
			if ( $aclCheck ) {
				foreach( $categories as $category ) {
					$aclResource = 'menu-cat-'.$category['id'];
					if ( !$this->_acl->resourceExists( $aclResource ) || !$this->_acl->check( $aclResource ) ) {
						unset( $categories[ $category['id'] ] );
					}
				}
			}
			return $categories;
		}

		/**
		 * Checks if a menu category exists by ID only.
		 *
		 * @param int $category
		 * @oaram bool $aclCheck
		 * @return bool
		 */
		public function categoryExists( $category, $aclCheck=true ) {
			return array_key_exists( $category, $this->getAllCategories($aclCheck) );
		}

		/**
		 * Gets details for a menu category via ID. If set to check ACL
		 * it will only return details if user has permission to the
		 * category.
		 *
		 * @param int $category
		 * @param bool $aclCheck
		 * @return array
		 */
		public function getCategory( $category, $aclCheck=true ) {
			$categories = $this->getAllCategories( $aclCheck );
			if ( isset( $categories[ $category ] ) ) {
				return $categories[ $category ];
			} else {
				throw new Menu_CategoryNoExist( $category );
			}
		}

		/**
		 * Gets every menu item for a menu category. Can also return all children
		 * that the menu item has as well.
		 *
		 * Can also check permission of the menu items, if user does not
		 * have permission they wont be returned.
		 *
		 * @param int $cid
		 * @param bool $flat			Flattern the 'children' key?
		 * @param bool $withChildren
		 * @param $aclCheck
		 * @return array
		 */
		public function getAllItems( $cid, $flat=false, $withChildren=true, $aclCheck=true ) {
			$category = $this->getCategory( $cid, $aclCheck );
			if ( !($items = $this->_cache->get('menu_items_'.$category['id'])) ) {
				$items = array();
				$pdoSt = $this->_sql->prepare( 'SELECT * FROM {SQL_PREFIX}mod_menu
												WHERE cat_id = ? AND heading_id = 0 ORDER BY `order` ASC ' );
				$pdoSt->execute( array($category['id']) );
				$tmpItems = $pdoSt->fetchAll( PDO::FETCH_ASSOC );
				$count = count( $tmpItems );
				for( $i=0; $i < $count; $i++ ) {
					// Add in some more array keys
					$tmpItems[ $i ]['depth'] = 0;
					$tmpItems[ $i ]['order_range'] = $count;
					$items[ $tmpItems[$i]['id'] ] = $tmpItems[ $i ];
				}
				$this->_cache->add( 'menu_items_'.$category['id'], $items );				
			}
			// Gather all subitems/children for this menu item
			foreach( $items as &$item ) {
				$resource = 'menu-item-'.$item['id'];
				if ( $aclCheck && (!$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource )) ) {
					unset( $items[ $item['id'] ] );
				} else if ( $withChildren ) {
					$item['children'] = $this->getChildItems( $item['id'], $aclCheck );
				}
			}
			return $flat ? zula_array_flatten($items, 'children') : $items;
		}

		/**
		 * Gets all child menu items for a given item ID. Can check ACL resource
		 * as well to limit the result set.
		 *
		 * @param int $itemId
		 * @param bool $aclCheck
		 * @return array
		 */
		public function getChildItems( $itemId, $aclCheck=true, $depth=1 ) {
			$itemId = abs( $itemId );
			if ( !($items = $this->_cache->get( 'menu_child_items_'.$itemId )) ) {
				$items = array();
				$query = $this->_sql->query( 'SELECT * FROM {SQL_PREFIX}mod_menu
											  WHERE heading_id = '.(int) $itemId.' ORDER BY `order` ASC' );
				$tmpItems = $query->fetchAll( PDO::FETCH_ASSOC );
				$count = count( $tmpItems );
				for( $i = 0; $i < $count; $i++ ) {
					$tmpItems[ $i ]['depth'] = $depth;
					$tmpItems[ $i ]['order_range'] = $count;
					$items[ $tmpItems[$i]['id'] ] = $tmpItems[ $i ];
				}
				$this->_cache->add( 'menu_child_items_'.$itemId, $items );
			}
			foreach( $items as $key=>$item ) {
				$resource = 'menu-item-'.$item['id'];
				if ( $aclCheck && (!$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource )) ) {
					unset( $items[ $key ]);
				} else {
					$items[ $key ]['children'] = $this->getChildItems( $item['id'], $aclCheck, $depth+1 );
				}
			}
			return $items;
		}

		/**
		 * Checks if a menu item exists by ID
		 *
		 * @param int $itemId
		 * @return bool
		 */
		public function itemExists( $itemId ) {
			try {
				$this->getItem( $itemId );
				return true;
			} catch ( Menu_ItemNoExist $e ) {
				return false;
			}
		}

		/**
		 * Gets details for a single menu item by ID
		 *
		 * @param int $itemId
		 * @return array
		 */
		public function getItem( $itemId ) {
			$query = $this->_sql->query( 'SELECT * FROM {SQL_PREFIX}mod_menu WHERE id = '.(int) $itemId );
			$item = $query->fetch( PDO::FETCH_ASSOC );
			$query->closeCursor();
			if ( $item ) {
				return $item; 
			} else {
				throw new Menu_ItemNoExist( $itemId );
			}
		}

		/**
		 * Adds a new menu category and returns the id of it
		 *
		 * @param string $name
		 * @return int|bool
		 */
		public function addCategory( $name ) {
			$pdoSt = $this->_sql->prepare( 'INSERT INTO {SQL_PREFIX}mod_menu_cats (name) VALUES (?)' );
			$pdoSt->execute( array($name) );
			if ( $pdoSt->rowCount() ) {
				$id = $this->_sql->lastInsertId();
				$this->_cache->delete( 'menu_categories' );
				Hooks::notifyAll( 'menu_add_category', $id, $name );
				return $id;
			} else {
				return false;
			}
		}

		/**
		 * Edits a menu category
		 *
		 * @param string $cid
		 * @param string $name
		 * @return bool
		 */
		public function editCategory( $cid, $name ) {
			$category = $this->getCategory( $cid );
			$pdoSt = $this->_sql->prepare( 'UPDATE {SQL_PREFIX}mod_menu_cats SET name = ? WHERE ID = ?' );
			$result = $pdoSt->execute( array($name, $category['id']) );
			if ( $result ) {
				$this->_cache->delete( 'menu_categories' );
				Hooks::notifyAll( 'menu_edit_category', $category['id'], $name );
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Deletes a menu category by ID, and all menu items under it.
		 *
		 * @param int $cit
		 * @return bool
		 */
		public function deleteCategory( $cid ) {
			$category = $this->getCategory( $cid );
			// Gather menu items, so we can remove their ACL resource and cache later
			$itemIds = $this->_sql->query( 'SELECT id FROM {SQL_PREFIX}mod_menu WHERE cat_id = '.(int) $category['id'] )
					  			  ->fetchAll( PDO::FETCH_COLUMN );
			// Remove the category and menu items
			$catRowCount = $this->_sql->query( 'DELETE FROM {SQL_PREFIX}mod_menu_cats WHERE id = '.(int) $category['id'] )
									  ->rowCount();
			$this->_sql->query( 'DELETE FROM {SQL_PREFIX}mod_menu WHERE cat_id = '.(int) $category['id'] )
					   ->closeCursor();
			if ( $catRowCount ) {
				$aclResources = array('menu-cat-'.$category['id']);
				$cacheKeys = array('menu_categories', 'menu_items_'.$category['id']);
				foreach( $itemIds as $id ) {
					$aclResources[] = 'menu-item-'.$id;
					$cacheKeys[] = 'menu_child_items_'.$id;					
				}
				$this->_acl->deleteResource( $aclResources );
				$this->_cache->delete( $cacheKeys );
				Hooks::notifyAll( 'menu_delete_category', $category['id'] );
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Adds a new menu item to a category
		 *
		 * @param int $cid
		 * @param string $name
		 * @param int $heading
		 * @param string $url
		 * @param string $attrTitle
		 * @return int|bool
		 */
		public function addItem( $cid, $name, $heading=0, $url='', $attrTitle='' ) {
			$category = $this->getCategory( $cid );
			// Get next order id
			$order = $this->_sql->query( 'SELECT MAX(`order`)+1 AS item_order FROM {SQL_PREFIX}mod_menu
										  WHERE cat_id = '.(int) $category['id'] )
							    ->fetch( PDO::FETCH_COLUMN );
			// Insert new item
			$heading = abs( $heading );
			$pdoSt = $this->_sql->prepare( 'INSERT INTO {SQL_PREFIX}mod_menu (cat_id, name, heading_id, url, attr_title, `order`)
											VALUES(?, ?, ?, ?, ?, ?)' );
			$pdoSt->execute( array(
								$category['id'], $name, $heading,
								$url, $attrTitle, (empty($order) ? 1 : $order) 
								));
			$pdoSt->closeCursor();
			if ( $pdoSt->rowCount() ) {
				$id = $this->_sql->lastInsertId();
				$this->_cache->delete( 'menu_items_'.$category['id'] );
				if ( $heading > 0 ) {
					$this->_cache->delete( 'menu_child_items_'.$heading );
				}
				Hooks::notifyAll( 'menu_add_item', $id );
				return $id;
			} else {
				return false;
			}
		}

		/**
		 * Edits a single menu item by ID
		 *
		 * @param int $id
		 * @param string $name
		 * @param int $heading
		 * @param string $url
		 * @param string $attrTitle
		 * @param int $order
		 * @return bool
		 */
		public function editItem( $id, $name, $heading=0, $url='', $attrTitle='', $order=null ) {
			$item = $this->getItem( $id );
			$details = array(
							'id'		=> $item['id'],
							'name'		=> $name,
							'heading'	=> abs($heading),
							'url'		=> $url,
							'attr_title'=> $attrTitle,
							'order'		=> $order,
							);
			$stmt = 'UPDATE {SQL_PREFIX}mod_menu SET name = :name, heading_id = :heading, url = :url, attr_title = :attr_title';			
			if ( is_null( $details['order'] ) ) {
				unset( $details['order'] );
			} else {
				$stmt .= ', `order` = :order';
			}
			$pdoSt = $this->_sql->prepare( $stmt.' WHERE id = :id' );
			if ( $pdoSt->execute( $details ) ) {
				$this->_cache->delete( array('menu_items_'.$item['cat_id'], 'menu_child_items_'.$item['heading_id']) );
				Hooks::notifyAll( 'menu_edit_item', $item['id'], $details );
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Deletes a menu item, and all items under it.
		 *
		 * @param int $id
		 * @return bool
		 */
		public function deleteItem( $id ) {
			$item = $this->getItem( $id );
			// Get all menu item IDs to delete ACL resources and cache later on
			$itemIds = $this->_sql->query( 'SELECT id, heading_id FROM {SQL_PREFIX}mod_menu
											WHERE id = '.(int) $item['id'].' OR heading_id = '.(int) $item['id'] )
								  ->fetchAll( PDO::FETCH_ASSOC );
			// Remove all menu items
			$pdoSt = $this->_sql->prepare( 'DELETE FROM {SQL_PREFIX}mod_menu WHERE id = :id OR heading_id = :id' );
			$pdoSt->execute( array(':id' => $item['id']) );
			if ( $pdoSt->rowCount() ) {
				$aclResources = array();
				$cacheKeys = array('menu_items_'.$item['cat_id']);
				foreach( $itemIds as $tmpItem ) {
					$aclResources[] = 'menu-item-'.$tmpItem['id'];
					$cacheKeys[] = 'menu_child_items_'.$tmpItem['heading_id'];
				}
				$this->_acl->deleteResource( $aclResources );
				$this->_cache->delete( $cacheKeys );
				Hooks::notifyAll( 'menu_delete_item', $item['id'], $item );
				return true;
			} else {
				return false;
			}
		}

	}

?>
