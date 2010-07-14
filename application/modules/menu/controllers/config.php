<?php

/**
 * Zula Framework Module (menu)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Menu
 */

	class Menu_controller_config extends Zula_ControllerBase {

		/**
		 * Constructor function
		 * Configure common page links
		 */
		public function __construct( $moduleDetails, $config, $sector ) {
			parent::__construct( $moduleDetails, $config, $sector );
			$this->setPageLinks( array(
										t('Manage Categories') 	=> $this->_router->makeUrl( 'menu', 'config' ),
										t('Add Category')		=> $this->_router->makeUrl( 'menu', 'config', 'addcat'),
										));
		}

		/**
		 * Displays every category and every item that the user has access to in
		 * a nicely formatted table with the option to Edit/Delete
		 *
		 * @return string
		 */
		public function indexSection() {
			// User must have access to at least one of the following ACL rules:
			$aclRules = array( 'menu_edit_cat', 'menu_delete_cat', 'menu_add_item', 'menu_edit_item', 'menu_delete_item' );
			if ( !$this->_acl->checkMulti( $aclRules ) ) {
				throw new Module_NoPermission;
			}
			$this->setTitle( t('Manage Menu Categories') );
			$this->setOutputType( self::_OT_CONFIG );
			// Build and output main view
			$view = $this->loadView( 'config/main.html' );
			$view->assign( array('CATEGORIES' => $this->_model()->getAllCategories()) );
			$view->assignHtml( array('CSRF' => $this->_input->createToken( true )) );
			return $view->getOutput();
		}

		/**
		 * Shows form and handles adding of a new menu category
		 *
		 * @return string
		 */
		public function addCatSection() {
			if ( !$this->_acl->check( 'menu_add_cat' ) ) {
				throw new Module_NoPermission;
			}
			$this->setTitle( t('Add Menu Category') );
			$this->setOutputType( self::_OT_CONFIG );
			// Prepare form validation
			$form = $this->buildCategoryForm();
			if ( $form->hasInput() && $form->isValid() ) {
				// Add the the new menu category ald ACL resource
				$fd = $form->getValues( 'menu' );
				$cid = $this->_model()->addCategory( $fd['name'] );
				try {
					$roles = $this->_input->post( 'acl_resources/menu-cat' );
				} catch ( Input_KeyNoExist $e ) {
					$roles = array();
				}
				$this->_acl->allowOnly( 'menu-cat-'.$cid, $roles );
				$this->_event->success( t('Added menu category') );
				return zula_redirect( $this->_router->makeUrl( 'menu', 'config', 'editcat', null, array('id' => $cid) ) );
			}
			return $form->getOutput();
		}

		/**
		 * Edits general details for a menu category (name and permission)
		 *
		 * @return string
		 */
		public function editCatSection() {
			if ( !$this->_acl->check( 'menu_edit_cat' ) ) {
				throw new Module_NoPermission;
			}
			$this->setTitle( t('Edit Menu Category') );
			$this->setOutputType( self::_OT_CONFIG );
			// Attempt to get category details and check permission
			try {
				$category = $this->_model()->getCategory( $this->_router->getArgument('id') );
				// Set page title
				$this->setTitle( sprintf( t('Edit Menu Category "%s"'), $category['name'] ), false );
				$aclResource = 'menu-cat-'.$category['id'];
				if ( !$this->_acl->resourceExists( $aclResource ) || !$this->_acl->check( $aclResource ) ) {
					throw new Module_NoPermission;
				} else {
					// Prepare form validation
					$form = $this->buildCategoryForm( $category['name'], $category['id'] );
					if ( $form->hasInput() && $form->isValid() ) {
						$fd = $form->getValues( 'menu' );
						$this->_model()->editCategory( $category['id'], $fd['name'] );
						$this->_event->success( sprintf( t('Edited menu category "%s"'), $fd['name'] ) );
						// Update ACL resource
						try {
							$roles = $this->_input->post( 'acl_resources/menu-cat-'.$category['id'] );
						} catch ( Input_KeyNoExist $e ) {
							$roles = array();
						}
						$this->_acl->allowOnly( 'menu-cat-'.$category['id'], $roles );
					} else {
						return $form->getOutput();
					}
				}
			} catch ( Router_ArgNoExist $e ) {
				$this->_event->error( t('No menu category selected') );
			} catch ( Menu_CategoryNoExist $e ) {
				$this->_event->error( t('Menu category does not exist') );
			}
			return zula_redirect( $this->_router->makeUrl( 'menu', 'config' ) );
		}

		/**
		 * Builds the correct view file and will populate it with default
		 * values if need be. Menu items will be fetched if it exists
		 *
		 * @param string $catName
		 * @param int $cid
		 * @return object
		 */
		protected function buildCategoryForm( $catName=null, $cid=null ) {
			// Build form
			if ( is_null( $cid ) ) {
				$op = 'add';
				$resource = 'menu-cat';
			} else {
				$op = 'edit';
				$resource = 'menu-cat-'.$cid;
				$items = $this->_model()->getAllItems( $cid, true );
			}
			$form = new View_Form( 'config/form_category.html', 'menu', is_null($cid) );
			$form->addElement( 'menu/name', $catName, t('Category Name'), array(new Validator_Alphanumeric, new Validator_Length(1, 32)) );
			// Add additional data
			$form->assign( array(
								'CAT_ID'	=> $cid,
								'ITEMS'		=> isset($items) ? $items : array(),
								'OP'		=> $op,
								));
			$form->assignHtml( array(
									'ACL_FORM' => $this->_acl->buildForm( array(t('View menu category') => $resource) )
									));
			return $form;
		}

		/**
		 * Deletes all selected menu categories, if user has permission
		 *
		 * @return string
		 */
		public function deleteCatSection() {
			if ( !$this->_acl->check( 'menu_delete_cat' ) ) {
				throw new Module_NoPermission;
			} else if ( $this->_input->checkToken() ) {
				$this->setTitle( t('Delete Menu Category') );
				$this->setOutputType( self::_OT_CONFIG );
				try {
					// Remove all categories
					$delCount = 0;
					foreach( $this->_input->post( 'menu_cids' ) as $cid ) {
						try {
							$aclResource = 'menu-cat-'.$cid;
							if ( !$this->_acl->resourceExists( $aclResource ) || !$this->_acl->check( $aclResource ) ) {
								throw new Module_NoPermission;
							}
							$this->_model()->deleteCategory( $cid );
							++$delCount;
						} catch ( Menu_CategoryNoExist $e ) {
						}
					}
					if ( $delCount > 0 ) {
						$this->_event->success( t('Menu categories deleted') );
					}
				} catch ( Input_KeyNoExist $e ) {
					$this->_event->error( t('No menu categories selected') );

				}
			} else {
				$this->_event->error( Input::csrfMsg() );
			}
			return zula_redirect( $this->_router->makeUrl( 'menu', 'config' ) );
		}

		/**
		 * Allows the user to add a menu item to a category
		 * if it exists and permissions are correct
		 *
		 * @return string
		 */
		public function addItemSection() {
			if ( !$this->_acl->check( 'menu_add_item' ) ) {
				// No permission to add an item
				throw new Module_NoPermission;
			}
			$this->setTitle( t('Add menu item') );
			$this->setOutputType( self::_OT_CONFIG );
			// Get details for the category and check permissions
			try {
				$category = $this->_model()->getCategory( $this->_router->getArgument('id') );
				$aclResource = 'menu-cat-'.$category['id'];
				if ( !$this->_acl->resourceExists( $aclResource ) || !$this->_acl->check( $aclResource ) ) {
					throw new Module_NoPermission;
				} else {
					/**
					 * Check if we are attaching to a parent menu item, then build
					 * and check form validation.
					 */
					try {
						$parent = abs( $this->_router->getArgument( 'parent' ) );
					} catch ( Router_ArgNoExist $e ) {
						$parent = null;
					}
					$form = $this->buildItemForm( $category['id'], '', $parent );
					if ( $form->hasInput() && $form->isValid() ) {
						// Attempt to add the new menu item and ACL resource
						$fd = $form->getValues( 'menu' );
						$itemId = $this->_model()->addItem( $category['id'], $fd['name'], $fd['parent'], $fd['url'], $fd['attr_title'] );
						$this->_event->success( t('Added menu item') );
						try {
							$roles = $this->_input->post( 'acl_resources/menu-item' );
						} catch ( Input_KeyNoExist $e ) {
							$roles = array();
						}
						$this->_acl->allowOnly( 'menu-item-'.$itemId, $roles );
						return zula_redirect( $this->_router->makeUrl( 'menu', 'config', 'editcat', null, array('id' => $category['id']) ) );
					}
					return $form->getOutput();
				}
			} catch ( Router_ArgNoExist $e ) {
				$this->_event->error( t('Menu category does not exist') );
			} catch ( Menu_CategoryNoExist $e ) {
				$this->_event->error( t('Menu category does not exist') );
			}
			return zula_redirect( $this->_router->makeUrl( 'menu', 'config' ) );
		}

		/**
		 * Allows the user to edit a menu item if it exists and
		 * user has correct permissions
		 *
		 * @return string
		 */
		public function editItemSection() {
			if ( !$this->_acl->check( 'menu_edit_item' ) ) {
				// No permission to edit an item
				throw new Module_NoPermission;
			}
			$this->setTitle( t('Edit Menu Item') );
			$this->setOutputType( self::_OT_CONFIG );
			// Attempt to get details for the menu item and category, checking permission
			try {
				$item = $this->_model()->getItem( $this->_router->getArgument('id') );
				$aclResource = 'menu-item-'.$item['id'];
				if ( !$this->_acl->resourceExists( $aclResource ) || !$this->_acl->check( $aclResource ) ) {
					throw new Module_NoPermission;
				}
				// Check parent category permission, as well
				$category = $this->_model()->getCategory( $item['cat_id'] );
				$aclResource = 'menu-cat-'.$category['id'];
				if ( !$this->_acl->resourceExists( $aclResource ) || !$this->_acl->check( $aclResource ) ) {
					throw new Module_NoPermission;
				} else {
					$form = $this->buildItemForm( $item['cat_id'], $item['name'], $item['heading_id'],
												  $item['url'], $item['attr_title'], $item['id'] );
					if ( $form->hasInput() && $form->isValid() ) {
						// Edit the menu item and ACL resource
						$fd = $form->getValues( 'menu' );
						$this->_model()->editItem( $item['id'], $fd['name'], $fd['parent'], $fd['url'], $fd['attr_title'] );
						$this->_event->success( sprintf( t('Edited menu item "%s"'), $fd['name'] ) );
						try {
							$roles = $this->_input->post( 'acl_resources/menu-item-'.$item['id'] );
						} catch ( Input_KeyNoExist $e ) {
							$roles = array();
						}
						$this->_acl->allowOnly( 'menu-item-'.$item['id'], $roles );
						return zula_redirect( $this->_router->makeUrl( 'menu', 'config', 'editcat', null, array('id' => $category['id']) ) );
					}
					return $form->getOutput();
				}
			} catch ( Router_ArgNoExist $e ) {
				$this->_event->error( t('No menu item selected') );
			} catch ( Menu_ItemNoExist $e ) {
				$this->_event->error( t('Menu item does not exist') );
			} catch ( Menu_CategoryNoExist $e ) {
				$this->_event->error( t('Menu category does not exist') );
			}
			return zula_redirect( $this->_router->makeUrl( 'menu', 'config' ) );
		}

		/**
		 * Builds the correct view form for either adding or editing
		 * a menu item, can also add in default values.
		 *
		 * @param int $cid
		 * @param string $name
		 * @param string $parentHeading
		 * @param string $url
		 * @param string $attrTitle
		 * @param string $itemId
		 * @return object
		 */
		protected function buildItemForm( $cid, $name=null, $parentHeading=0, $url=null, $attrTitle=null, $itemId=null ) {
			if ( is_null( $itemId ) ) {
				$op = 'add';
				$aclResource = 'menu-item';
			} else {
				$op = 'edit';
				$aclResource = 'menu-item-'.$itemId;
			}
			// Build form and validation
			$form = new View_Form( 'config/form_item.html', 'menu', is_null($itemId) );
			$form->addElement( 'menu/id', $itemId, 'ID', new Validator_Int, ($op == 'edit') );
			$form->addElement( 'menu/cat_id', $cid, 'Cat ID', new Validator_Int, ($op == 'add') );
			$form->addElement( 'menu/name', $name, t('Name'), new Validator_Length(1, 64) );
			$form->addElement( 'menu/parent', $parentHeading, t('Parent'), new Validator_Numeric );
			$form->addElement( 'menu/url', $url, 'URL', new Validator_Length(0, 255) );
			$form->addElement( 'menu/attr_title', $attrTitle, t('Attribute Text'), new Validator_Length(0, 255) );
			// Add additional vars
			$form->assign( array(
								'OP'		=> $op,
								'HEADINGS'	=> zula_array_flatten( $this->_model()->getAllItems($cid), 'children' ),
								));
			$form->assignHtml( array(
									'ACL_FORM' => $this->_acl->buildForm( array(t('View menu item') => $aclResource) ),
									));
			return $form;
		}

		/**
		 * Creates a bridge between the Delete Selected and Update Order
		 * functionaility, as there can only be one form with one action
		 *
		 * @return mixed
		 */
		public function bridgeSection() {
			$this->setOutputType( self::_OT_CONFIG );
			if ( !$this->_input->checkToken() ) {
				$this->_event->error( Input::csrfMsg() );
			} else if ( $this->_input->has( 'post', 'menu_delete' ) ) {
				// Delete all selected menu items
				if ( !$this->_acl->check( 'menu_delete_item' ) ) {
					throw new Module_NoPermission;
				}
				try {
					$delCount = 0;
					foreach( $this->_input->post( 'menu_ids' ) as $item ) {
						try {
							$resource = 'menu-item-'.$item;
							if ( $this->_acl->resourceExists( $resource ) && $this->_acl->check( $resource ) ) {
								$this->_model()->deleteItem( $item );
								++$delCount;
							}
						} catch ( Menu_ItemNoExist $e ) {
						}
					}
					if ( $delCount > 0 ) {
						$this->_event->success( t('Deleted menu items') );
					}
				} catch ( Input_KeyNoExist $e ) {
					$this->_event->error( t('No menu items selected') );
				}
			} else if ( $this->_input->has( 'post', 'menu_updateorder' ) ) {
				// Update order of all of the menu items
				if ( !$this->_acl->check( 'menu_edit_item' ) ) {
					throw new Module_NoPermission;
				}
				$execData = array();
				$sqlMiddle = null;
				foreach( $this->_input->post( 'menu_order' ) as $item=>$order ) {
					try {
						$item = $this->_model()->getItem( $item );
						$resource = 'menu-item-'.$item['id'];
						if ( $this->_acl->resourceExists( $resource ) && $this->_acl->check( $resource ) ) {
							// Clear cache for this menu item!
							$this->_cache->delete( array('menu_items_'.$item['cat_id'], 'menu_child_items_'.$item['id']) );
							$execData[] = $item['id'];
							$execData[] = abs( $order );
							$sqlMiddle .= 'WHEN id = ? THEN ? ';
						}
					} catch ( Menu_ItemNoExist $e ) {
					}
				}
				if ( $sqlMiddle !== null ) {
					$pdoSt = $this->_sql->prepare( 'UPDATE {SQL_PREFIX}mod_menu SET `order` = CASE '.$sqlMiddle.'ELSE `order` END' );
					$pdoSt->execute( $execData );
				}
				$this->_event->success( t('Menu order updated') );
			}
			try {
				$url = $this->_router->makeUrl( 'menu', 'config', 'editcat', null, array('id' => $this->_input->post('menu/cid')) );
			} catch ( Router_ArgNoExist $e ) {
				$url = $this->_router->makeUrl( 'menu', 'config' );
			}
			return zula_redirect( $url );
		}

	}

?>
