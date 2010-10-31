<?php

/**
 * Zula Framework Module (page)
 * --- Provides a way of creating static pages which can then be displayed
 * Also allows for parent/child relationships to provide a sort of 'book'
 * with full contents/index
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Page
 */

	class Page_controller_config extends Zula_ControllerBase {

		/**
		 * How many pages to display per-page with Pagination
		 */
		const _PER_PAGE = 12;

		/**
		 * Constructor
		 *
		 * @return object
		 */
		public function __construct( $moduleDetails, $config, $sector ) {
			parent::__construct( $moduleDetails, $config, $sector );
			if ( $this->_acl->check( 'page_manage' ) ) {
				$this->setPageLinks( array(
											t('Manage pages') 	=> $this->_router->makeUrl( 'page', 'config' ),
											t('Add page')		=> $this->_router->makeUrl( 'page', 'config', 'add' ),
											));
			}
		}

		/**
		 * Displays an overview of pages for a user to manage. Only the parent
		 * will be shown, non of the children will be.
		 *
		 * @return string
		 */
		public function indexSection() {
			$this->setTitle( t('Manage pages') );
			$this->setOutputType( self::_OT_CONFIG );
			if ( !$this->_acl->check( 'page_manage' ) ) {
				throw new Module_NoPermission;
			}
			// Check what pagination page we are on, and get all pages
			try {
				$curPage = abs( $this->_input->get('page')-1 );
			} catch ( Input_KeyNoExist $e ) {
				$curPage = 0;
			}
			$pages = $this->_model()->getAllPages( self::_PER_PAGE, ($curPage*self::_PER_PAGE), 0 );
			$pageCount = $this->_model()->getCount();
			if ( $pageCount > 0 ) {
				$pagination = new Pagination( $pageCount, self::_PER_PAGE );
			}
			// Build and return view
			$view = $this->loadView( 'config/overview.html' );
			$view->assign( array('PAGES' => $pages) );
			$view->assignHtml( array(
									'PAGINATION'	=> isset($pagination) ? $pagination->build() : '',
									'CSRF'			=> $this->_input->createToken( true ),
									));
			// Autocomplete/suggest feature
			$this->_theme->addJsFile( 'jquery.autocomplete' );
			$this->_theme->addCssFile( 'jquery.autocomplete.css' );
			$this->addAsset( 'js/autocomplete.js' );
			return $view->getOutput();
		}

		/**
		 * Autocomplete/autosuggest JSON response
		 *
		 * @return false
		 */
		public function autocompleteSection() {
			try {
				$query = $this->_input->get( 'query' );
				$searchTitle = '%'.str_replace( '%', '\%', $query ).'%';
				$pdoSt = $this->_sql->prepare( 'SELECT id, title FROM {PREFIX}mod_page WHERE title LIKE ?' );
				$pdoSt->execute( array($searchTitle) );
				// Setup the object to return
				$jsonObj = new StdClass;
				$jsonObj->query = $query;
				$jsonObj->suggestions = array();
				$jsonObj->data = array();
				foreach( $pdoSt->fetchAll( PDO::FETCH_ASSOC ) as $row ) {
					if ( $this->_acl->check( 'page-edit_'.$row['id'] ) ) {
						$jsonObj->suggestions[] = $row['title'];
						$jsonObj->data[] = $this->_router->makeFullUrl( 'page', 'config', 'edit', 'admin', array('id' => $row['id']) );
					}
				}
				header( 'Content-Type: text/javascript; charset=utf-8' );
				echo json_encode( $jsonObj );
				return false;
			} catch ( Input_KeyNoExist $e ) {
				trigger_error( $e->getMessage(), E_USER_ERROR );
			}
		}

		/**
		 * Shows form and handles adding of a new page
		 *
		 * @return string
		 */
		public function addSection() {
			$this->setTitle( t('Add page') );
			$this->setOutputType( self::_OT_CONFIG | self::_OT_CONTENT_STATIC );
			// Check if there is a parent ID for the page to be attached to
			try {
				$pid = abs( $this->_router->getArgument( 'pid' ) );
			} catch ( Router_ArgNoExist $e ) {
				try {
					$pid = abs( $this->_input->post('page/parent') );
				} catch ( Input_KeyNoExist $e ) {
					$pid = 0;
				}
			}
			if ( $pid ) {
				try {
					$parent = $this->_model()->getPage( $pid );
					// Check permission
					$resource = 'page-manage_'.$parent['id'];
					if ( !$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource ) ) {
						throw new Module_NoPermission;
					}
					$this->setTitle( sprintf( t('Add subpage to "%s"'), $parent['title'] ) );
				} catch ( Page_NoExist $e ) {
					$this->_event->error( t('Parent page does not exist') );
					return zula_redirect( $this->_router->makeUrl( 'page', 'config' ) );
				}
			} else if ( !$this->_acl->check( 'page_manage' ) ) {
				throw new Module_NoPermission;
			}
			// Build form and check it is all valid
			$form = $this->buildForm( $pid );
			if ( $form->hasInput() && $form->isValid() ) {
				$fd = $form->getValues( 'page' );
				if ( !isset( $fd['parent'] ) ) {
					$fd['parent'] = 0;
				}
				$page = $this->_model()->add( $fd['title'], $fd['body'], $fd['parent'] );
				// Update ACL resources
				foreach( array('page-view_', 'page-edit_', 'page-manage_') as $resource ) {
					try {
						$roles = $this->_input->post( 'acl_resources/'.$resource );
					} catch ( Input_KeyNoExist $e ) {
						$roles = array();
					}
					$this->_acl->allowOnly( $resource.$page['id'], $roles );
				}
				// Redirect back to correct location
				$form->success( 'page/index/'.$page['identifier'] );
				$this->_event->success( t('Added new page') );
				if ( empty( $parent ) ) {
					$url = $this->_router->makeUrl( 'page', 'config' );
				} else {
					$treePath = $this->_model()->findPath( $fd['parent'] );
					$url = $this->_router->makeUrl( 'page', 'config', 'edit', null, array('id' => $treePath[0]['id']) );
				}
				return zula_redirect( $url );
			}
			return $form->getOutput();
		}

		/**
		 * Displays the form for and handling editing of a page
		 *
		 * @return string
		 */
		public function editSection() {
			$this->setTitle( t('Edit page') );
			$this->setOutputType( self::_OT_CONFIG | self::_OT_CONTENT_STATIC );
			// Get details of the page to edit
			try {
				$page = $this->_model()->getPage( $this->_router->getArgument('id') );
				// Check permission
				$resource = 'page-edit_'.$page['id'];
				if ( !$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource ) ) {
					throw new Module_NoPermission;
				}
				$quickEdit = $this->_router->hasArgument( 'qe' );
				if ( $quickEdit ) {
					$this->setTitle( sprintf( t('Quick edit page "%s"'), $page['title'] ) );
				} else {
					$this->setTitle( sprintf( t('Edit page "%s"'), $page['title'] ) );
				}
				// Build the form and check it is all valid
				$form = $this->buildForm( $page['parent'], $page['id'], $page['title'], $page['body'], $quickEdit );
				$form->setContentUrl( 'page/index/'.$page['identifier'] );
				if ( $form->hasInput() && $form->isValid() ) {
					$fd = $form->getValues( 'page' );
					if ( !isset( $fd['parent'] ) ) {
						$fd['parent'] = 0; # Default to no parent
					}
					$this->_model()->edit( $page['id'], $fd['title'], $fd['body'], $fd['parent'] );
					if ( $this->_acl->check( 'page-manage_'.$page['id'] ) ) {
						// Update ACL resources
						foreach( array('page-view_', 'page-edit_', 'page-manage_') as $resource ) {
							try {
								$roles = $this->_input->post( 'acl_resources/'.$resource.$page['id'] );
							} catch ( Input_KeyNoExist $e ) {
								$roles = array();
							}
							$this->_acl->allowOnly( $resource.$page['id'], $roles );
						}
					}
					// Redirect back to correct URL
					$form->success( 'page/index/'.$page['identifier'] );
					$this->_event->success( t('Edited page') );
					if ( $quickEdit ) {
						$url = $this->_router->makeUrl( 'page', 'index', $page['identifier'] );
					} else if ( empty( $fd['parent'] ) ) {
						$url = $this->_router->makeUrl( 'page', 'config' );
					} else {
						$treePath = $this->_model()->findPath( $fd['parent'] );
						$url = $this->_router->makeUrl( 'page', 'config', 'edit', null, array('id' => $treePath[0]['id']) );
					}
					return zula_redirect( $url );
				}
				return $form->getOutput();
			} catch ( Router_ArgNoExist $e ) {
				$this->_event->error( t('No page selected') );
			} catch ( Page_NoExist $e ) {
				$this->_event->error( t('Page does not exist') );
			}
			return zula_redirect( $this->_router->makeUrl('page', 'config') );
		}

		/**
		 * Builds the form view that will allow users to add or edit a page.
		 *
		 * @param int $parent
		 * @param int $id
		 * @param string $title
		 * @param string $body
		 * @param bool $isQuickEdit
		 * @return object
		 */
		protected function buildForm( $parent=null, $id=null, $title=null, $body=null, $isQuickEdit=false ) {
			$parent = abs( $parent );
			$validParents = array(0, $parent);
			if ( $id === null ) {
				$op = 'add';
			} else {
				$op = 'edit';
				/**
				 * Gather all children and find out all possible parents that this page
				 * can be part of, not including sub-children of its self.
				 */
				$children = $this->_model()->getChildren( $id );
				if ( $parent ) {
					$treePath = $this->_model()->findPath( $id );
					$possibleParents = $this->_model()->getChildren( $treePath[0]['id'], true, array($id) );
					array_unshift( $possibleParents, $treePath[0] );
				} else {
					$possibleParents = $this->_model()->getAllPages( 0, 0, $parent );
				}
				foreach( $possibleParents as $key=>$tmpParent ) {
					if ( $this->_acl->check( 'page-manage_'.$tmpParent['id'] ) && $tmpParent['id'] != $id ) {
						if ( !isset( $tmpParent['depth'] ) ) {
							$possibleParents[ $key ]['depth'] = 0;
						}
						$validParents[] = $tmpParent['id'];
					} else {
						unset( $possibleParents[ $key ] );
					}
				}
			}
			// Setup the correct ACL resources
			if ( $id === null || $this->_acl->check( 'page-manage_'.$id ) ) {
				$aclForm = $this->_acl->buildForm( array(
														t('View page')	=> 'page-view_'.$id,
														t('Edit page')	=> array('page-edit_'.$id, 'group_admin'),
														t('Delete, edit, add subpages & manage permissions') => array('page-manage_'.$id, 'group_admin')
														));
			} else {
				$aclForm = null;
			}
			// Build up the form
			$form = new View_Form( 'config/form_page.html', 'page', ($op == 'add') );
			$form->addElement( 'page/id', $id, 'ID', new Validator_Int, ($op == 'edit') );
			$form->addElement( 'page/title', $title, t('Title'), new Validator_Length(2, 255) );
			$form->addElement( 'page/parent', $parent, t('Parent'), new Validator_InArray($validParents), !empty($parent) );
			$form->addElement( 'page/body', $body, t('Body'), new Validator_Length(1, 50000) );
			$form->assign( array(
								'OP'		=> $op,
								'PARENTS'	=> isset($possibleParents) ? $possibleParents : null,
								'QUICK_EDIT'=> $isQuickEdit,
								));
			$form->assignHtml( array(
									'ACL_FORM'	=> $aclForm,
									'CHILDREN'	=> empty($children) ? null : $this->createChildRows( $children ),
									));
			return $form;
		}

		/**
		 * Creates the table rows for the pages children recursively
		 *
		 * @param array $children
		 * @param int $depth
		 * @return array
		 */
		protected function createChildRows( $children, $depth=0, &$i=0 ) {
			$rows = null;
			$childrenCount = count( $children );
			$orderId = 1; # Keep track of the order
			foreach( $children as $child ) {
				$view = $this->loadView( 'config/child_row.html' );
				$view->assign( array(
									'CHILD'		=> $child,
									'DEPTH'		=> $depth,
									'STYLE'		=> empty($child['children']) ? zula_odd_even($i) : 'subheading',
									'PREFIX'	=> str_pad( '- ', $depth+1, '-', STR_PAD_LEFT ),
									'COUNT'		=> $childrenCount,
									'ORDERID'	=> $orderId,
									));
				++$i;
				if ( empty( $child['children'] ) ) {
					$rows .= $view->getOutput();
				} else {
					$childRow = $view->getOutput();
					$childRow .= $this->createChildRows( $child['children'], $depth+4, $i );
					$rows .= $childRow;
				}
				++$orderId;
			}
			return $rows;
		}

		/**
		 * Gets which page(s) to delete and tries to delete them
		 *
		 * @return mixed
		 */
		public function deleteSection() {
			$this->setOutputType( self::_OT_CONFIG );
			try {
				$pids = $this->_input->post( 'page_ids' );
				$method = 'post';
				$url = $this->_router->makeUrl( 'page', 'config' );
			} catch ( Input_KeyNoExist $e ) {
				try {
					$pids = $this->_router->getArgument( 'id' );
					$method = 'get';
					$url = $this->_router->makeUrl( '/' );
				} catch ( Router_ArgNoExist $e ) {
					$this->_event->error( t('No pages selected') );
					return zula_redirect( $this->_router->makeUrl('page', 'config') );
				}
			}
			if ( $this->_input->checkToken( $method ) ) {
				$count = count( $pids );
				foreach( (array) $pids as $pid ) {
					if ( $this->_acl->check( 'page-manage_'.$pid ) ) {
						try {
							$this->_model()->delete( $pid );
						} catch ( Page_NoExist $e ) {
							if ( $count == 1 ) {
								throw new Module_ControllerNoExist;
							}
						}
					} else if ( $count == 1 ) {
						throw new Module_NoPermission;
					}
				}
				$this->_event->success( t('Deleted selected pages') );
			} else {
				$this->_event->error( Input::csrfMsg() );
			}
			return zula_redirect( $url );
		}

		/**
		 * Bridges between deleting a page, or update the order. This is only called
		 * when deleting or ordering children, not for deleting single pages.
		 *
		 * @return mixed
		 */
		public function bridgeSection() {
			$this->setOutputType( self::_OT_CONFIG );
			if ( !$this->_input->checkToken() ) {
				$this->_event->error( Input::csrfMsg() );
			} else if ( $this->_input->has( 'post', 'page_delete' ) ) {
				$this->setTitle( t('Delete Page') );
				try {
					foreach( $this->_input->post('page_ids') as $pid ) {
						if ( $this->_acl->check( 'page-manage_'.$pid ) ) {
							try {
								$this->_model()->delete( $pid );
							} catch ( Page_NoExist $e ) {
							}
						}
					}
					$this->_event->success( t('Deleted selected pages') );
				} catch ( Input_KeyNoExist $e ) {
					$this->_event->error( t('No pages selected') );
				}
			} else if ( $this->_input->has( 'post', 'page_update_order' ) ) {
				$this->setTitle( t('Update Page Order') );
				$execData = array();
				$sqlMiddle = null;
				foreach( $this->_input->post( 'page_order' ) as $pid=>$order ) {
					$pid = abs( $pid );
					if ( $this->_acl->check( 'page-manage_'.$pid ) ) {
						$execData[] = $pid;
						$execData[] = abs( $order );
						$sqlMiddle .= 'WHEN id = ? THEN ? ';
					}
				}
				if ( $sqlMiddle !== null ) {
					$pdoSt = $this->_sql->prepare( 'UPDATE {PREFIX}mod_page SET `order` = CASE '.$sqlMiddle.'ELSE `order` END' );
					$pdoSt->execute( $execData );
				}
				$this->_event->success( t('Page order updated') );
			}
			try {
				$parent = $this->_input->post( 'page_parent' );
				$url = $this->_router->makeUrl( 'page', 'config', 'edit', null, array('id' => $parent) );
			} catch ( Input_KeyNoExist $e ) {
				$url = $this->_router->makeUrl( 'page', 'config' );
			}
			return zula_redirect( $url );
		}

	}

?>
