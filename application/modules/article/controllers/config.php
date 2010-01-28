<?php

/**
 * Zula Framework Module (article)
 * --- Publish articles which can have multiple parts
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Article
 */

	class Article_controller_config extends Zula_ControllerBase {

		/**
		 * Amount of articles to display per page with pagination
		 */
		const _PER_PAGE = 12;

		/**
		 * Constructor function
		 */
		public function __construct( $moduleDetails, $config, $sector ) {
			parent::__construct( $moduleDetails, $config, $sector );
			$this->setPageLinks( array(
										t('Manage Articles')	=> $this->_router->makeUrl( 'article', 'config' ),
										t('Manage Categories')	=> $this->_router->makeUrl( 'article', 'config', 'cats' ),
										t('Add Category')		=> $this->_router->makeUrl( 'article', 'config', 'addcat'),
										t('Add Article')		=> $this->_router->makeUrl( 'article', 'config', 'add'),
										t('Settings')			=> $this->_router->makeUrl( 'article', 'config', 'settings' ),
										));
		}

		/**
		 * Displays all articles with pagination (including unpublished articles).
		 * It will also delete all selected articles.
		 *
		 * @return string|bool
		 */
		public function indexSection() {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('Manage Articles') );
			$this->setOutputType( self::_OT_CONFIG );
			if ( $this->_input->checkToken() ) {
				// Delete all selected articles
				if ( !$this->_acl->check( 'article_delete_article' ) ) {
					throw new Module_NoPermission;
				}
				try {
					$ids = $this->_input->post( 'article_ids' );
					$count = 0;
					foreach( $ids as $aid ) {
						try {
							$article = $this->_model()->getArticle( $aid );
							// Check user permission
							$resource = 'article-cat-'.$article['cat_id'];
							if ( $this->_acl->resourceExists( $resource ) && $this->_acl->check( $resource ) ) {
								$this->_model()->deleteArticle( $article['id'] );
								++$count;
							}
						} catch ( Article_NoExist $e ) {
						}
					}
					if ( $count ) {
						$this->_event->success( t('Deleted selected articles') );
					}
				} catch ( Input_KeyNoExist $e ) {
					$this->_event->error( t('No articles selected') );
				}
				return zula_redirect( $this->_router->makeUrl( 'article', 'config' ) );
			} else if ( $this->_acl->checkMulti( array('article_add_article', 'article_edit_article', 'article_delete_article') ) ) {
				/**
				 * Attempt to get details for the category if one was provided
				 */
				try {
					if ( ($cid = $this->_input->get('cid')) != false ) {
						$category = $this->_model()->getCategory( $cid );
						// Check user has permission to this category
						$resource = 'article-cat-'.$category['id'];
						if ( $this->_acl->resourceExists( $resource ) && $this->_acl->check( $resource ) ) {
							$this->setTitle( sprintf( t('Manage "%s" Articles'), $category['title'] ) );
						} else {
							throw new Module_NoPermission;
						}
					}
				} catch ( Input_KeyNoExist $e ) {
					$cid = null;
				} catch ( Article_CatNoExist $e ) {
					$cid = null;
				}
				// Get the page which we are on
				try {
					$curPage = abs( $this->_input->get('page')-1 );
				} catch ( Input_KeyNoExist $e ) {
					$curPage = 0;
				}
				$articles = $this->_model()->getAllArticles( self::_PER_PAGE, ($curPage*self::_PER_PAGE), $cid, true );
				$articleCount = $this->_model()->getCount();
				if ( $articleCount > 0 ) {
					$pagination = new Pagination( $articleCount, self::_PER_PAGE );
				}
				// Build up the main view
				$view = $this->loadView( 'config/overview.html' );
				$view->assign( array(
									'ARTICLES'		=> $articles,
									'COUNT'			=> $articleCount,
									'CATEGORIES'	=> $this->_model()->getAllCategories(),
									'CURRENT_CID'	=> $cid,
									));
				$view->assignHtml( array(
										'PAGINATION'	=> isset($pagination) ? $pagination->build() : '',
										'CSRF'			=> $this->_input->createToken(true),
										));
				$this->_theme->addJsFile( 'jquery.autocomplete' );
				$this->_theme->addCssFile( 'jquery.autocomplete.css' );
				$this->addAsset( 'js/autocomplete.js' );
				return $view->getOutput();
			} else {
				throw new Module_NoPermission;
			}
		}

		/**
		 * Autocomplete/autosuggest JSON response
		 *
		 * @return false
		 */
		public function autocompleteSection() {
			if ( !_AJAX_REQUEST ) {
				throw new Module_AjaxOnly;
			}
			header( 'Content-Type: text/javascript; charset=utf-8' );
			$searchTitle = '%'.str_replace( '%', '\%', $this->_input->get('query') ).'%';
			$query = 'SELECT id, cat_id, title FROM {SQL_PREFIX}mod_articles WHERE title LIKE ?';
			if ( $this->_router->hasArgument('catId') ) {
				$query .= ' AND cat_id = '.(int) $this->_router->getArgument('catId');
			}
			$pdoSt = $this->_sql->prepare( $query );
			$pdoSt->execute( array($searchTitle) );
			// Setup the object to return
			$jsonObj = new StdClass;
			$jsonObj->query = $this->_input->get( 'query' );
			foreach( $pdoSt->fetchAll( PDO::FETCH_ASSOC ) as $row ) {
				$resource = 'article-cat-'.$row['cat_id'];
				if ( $this->_acl->resourceExists( $resource ) && $this->_acl->check( $resource ) ) {
					$jsonObj->suggestions[] = $row['title'];
					$jsonObj->data[] = $this->_router->makeFullUrl( 'article', 'config', 'edit', 'admin', array('id' => $row['id']) );
				}
			}
			echo json_encode( $jsonObj );
			return false;
		}

		/**
		 * Displays an overview of all categories available. It will also delete
		 * all selected categories.
		 *
		 * @return string|bool
		 */
		public function catsSection() {
			if ( !$this->_acl->checkMulti( array('article_edit_cat', 'article_delete_cat', 'article_add_cat') ) ) {
				throw new Module_NoPermission;
			}
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('Manage Categories') );
			$this->setOutputType( self::_OT_CONFIG );
			if ( $this->_input->checkToken() ) {
				/**
				 * Delete all selected categories
				 */
				if ( !$this->_acl->check( 'article_delete_cat' ) ) {
					throw new Module_NoPermission;
				}
				try {
					$count = 0;
					foreach( $this->_input->post( 'article_cids' ) as $cid ) {
						$resource = 'article-cat-'.$cid;
						if ( $this->_acl->resourceExists( $resource ) && $this->_acl->check( $resource ) ) {
							$this->_model()->deleteCategory( $cid );
							++$count;
						}
					}
					if ( $count ) { 
						$this->_event->success( t('Deleted selected categories') );
					}					
				} catch ( Input_KeyNoExist $e ) {
					$this->_event->error( t('No categories selected') );
				}
				return zula_redirect( $this->_router->makeUrl( 'article', 'config', 'cats' ) );
			} else {
				/**
				 * Display categories with their article count
				 */
				$categories = array();
				foreach( $this->_model()->getAllCategories() as $cat ) {
					$cat['count'] = $this->_model()->countArticles( $cat['id'] );
					$categories[] = $cat;
				}
				$view = $this->loadView( 'config/categories.html' );
				$view->assign( array('CATEGORIES' => $categories) );
				$view->assignHtml( array(
										'CSRF'	=> $this->_input->createToken( true ),
										));
				return $view->getOutput();
			}
		}

		/**
		 * Displays and handles adding of a new category
		 *
		 * @return string|bool
		 */
		public function addCatSection() {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('Add Article Category') );
			$this->setOutputType( self::_OT_CONFIG );
			if ( !$this->_acl->check( 'article_add_cat' ) ) {
				throw new Module_NoPermission;
			}
			// Prepare form
			$form = $this->buildCategoryForm();
			if ( $form->hasInput() && $form->isValid() ) {
				$fd = $form->getValues( 'article' );
				try {
					$details = $this->_model()->addCategory( $fd['title'], $fd['description'] );
					$this->_event->success( t('Added article category') );
					// Update ACL resource
					try {
						$roles = $this->_input->post( 'acl_resources/article-cat' );
					} catch ( Input_KeyNoExist $e ) {
						$roles = array();
					}
					$this->_acl->allowOnly( 'article-cat-'.$details['id'], $roles );
				} catch ( Exception $e ) {
					$this->_event->error( $e->getMessage() );
				}
				return zula_redirect( $this->_router->makeUrl( 'article', 'config', 'cats' ) );
			}
			return $form->getOutput();
		}

		/**
		 * Edits an existing article category if the user has permission to it
		 *
		 * @return string|bool
		 */
		public function editCatSection() {
			$this->setTitle( t('Edit Article Category') );
			$this->_locale->textDomain( $this->textDomain() );
			$this->setOutputType( self::_OT_CONFIG );
			if ( !$this->_acl->check( 'article_edit_cat' ) ) {
				throw new Module_NoPermission;
			}
			// Get category ID and ensure user has permission to it
			try {
				$cid = $this->_router->getArgument( 'id' );
				$category = $this->_model()->getCategory( $cid );
				$resource = 'article-cat-'.$category['id'];
				if ( !$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource ) ) {
					throw new Module_NoPermission;
				}
				// Prepare form validation
				$form = $this->buildCategoryForm( $category['title'], $category['description'], $category['id'] );
				if ( $form->hasInput() && $form->isValid() ) {
					$fd = $form->getValues( 'article' );
					$this->_model()->editCategory( $category['id'], $fd['title'], $fd['description'] );
					$this->_event->success( t('Edited article category') );
					// Update ACL resource
					try {
						$roles = $this->_input->post( 'acl_resources/article-cat-'.$category['id'] );
					} catch ( Input_KeyNoExist $e ) {
						$roles = array();
					}
					$this->_acl->allowOnly( 'article-cat-'.$category['id'], $roles );
				} else {
					return $form->getOutput();
				}
			} catch ( Router_ArgNoExist $e ) {
				$this->_event->error( t('No category selected') );
			} catch ( Article_CatNoExist $e ) {
				$this->_event->error( t('Article category does not exist') );
			}
			return zula_redirect( $this->_router->makeUrl( 'article', 'config', 'cats' ) );
		}

		/**
		 * Builds a form that allows users to add or edit a category
		 * and can fill the values with default values
		 *
		 * @param string $title
		 * @param string $desc
		 * @param int $id
		 * @return string
		 */
		protected function buildCategoryForm( $title=null, $desc=null, $id=null ) {
			$op = is_null($id) ? 'add' : 'edit';
			$form = new View_Form( 'config/form_category.html', 'article', is_null($id) );
			$form->action( $this->_router->makeUrl( 'article', 'config', $op.'cat', null, array('id' => $id) ) );
			$form->addElement( 'article/title', $title, t('Title'), new Validator_Length(1, 255) );
			$form->addElement( 'article/description', $desc, t('Description'), new Validator_Length(0, 255) );
			// Set op and other tags
			$form->assign( array('OP' => $op, 'ID' => $id) );
			$resource = $op == 'edit' ? 'article-cat-'.$id : 'article-cat';
			$form->assignHtml( array(
									'ACL_FORM' => $this->_acl->buildForm( array(t('View category') => $resource) ),
									));
			return $form;
		}

		/**
		 * Displays and handles adding of a new article, and an initial article part.
		 *
		 * @return string|bool
		 */
		public function addSection() {
			$this->setTitle( t('Add Article') );
			$this->_locale->textDomain( $this->textDomain() );
			$this->setOutputType( self::_OT_CONFIG );
			if ( !$this->_acl->check( 'article_add_article' ) ) {
				throw new Module_NoPermission;
			}
			// Gather all categories and insure we actually have some!
			$categories = $this->_model()->getAllCategories();
			if ( count( $categories ) == 0 ) {
				$this->_event->error( t('Currently no categories to add an article to') );
				return zula_redirect( $this->_router->makeUrl( 'article', 'config' ) );
			}
			// Prepare form validation
			$form = new View_form( 'config/form_article_add.html', 'article' );
			$form->addElement( 'article/cid', null, t('Category'), new Validator_Int );
			$form->addElement( 'article/title', null, t('Title'), new Validator_Length(1, 255) );
			$form->addElement( 'article/published', true, t('Published'), new Validator_Bool );
			$form->addElement( 'article/part_title', null, t('Part Title'), new Validator_Length(0, 255) );
			$form->addElement( 'article/part_body', null, t('Content'), new Validator_Length(1, 65535) );
			if ( $form->hasInput() && $form->isValid() ) {
				$fd = $form->getValues( 'article' );
				/**
				 * Get details for the selected category and check user has permission to it
				 */
				try {
					$category = $this->_model()->getCategory( $fd['cid'] );
					$resource = 'article-cat-'.$category['id'];
					if ( !$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource ) ) {
						throw new Module_NoPermission;
					} else {
						$details = $this->_model()->addArticle( $category['id'], $fd['title'], $fd['part_body'],
																$fd['part_title'], $fd['published'] );
						$form->success( 'article/view/'.$details['clean_title'] );
						$this->_event->success( t('Added article') );
						return zula_redirect( $this->_router->makeUrl( 'article', 'config', 'edit', null, array('id' => $details['id']) ) );
					}
				} catch ( Article_CatNoExist $e ) {
					$this->_event->error( t('Article category does not exist') );
				}
			}
			$form->assign( array('CATEGORIES' => $categories) );
			return $form->getOutput();
		}

		/**
		 * Handles editing of an existing article, assuming user has permission
		 * to the parent category.
		 *
		 * @return string|bool
		 */
		public function editSection() {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setOutputType( self::_OT_CONFIG );
			if ( !$this->_acl->check( 'article_edit_article' ) ) {
				throw new Module_NoPermission;
			}
			// Get the ID of the article we're to be editing
			try {
				$aid = $this->_router->getArgument( 'id' );
				$article = $this->_model()->getArticle( $aid );
				// Check user has permission
				$resource = 'article-cat-'.$article['cat_id'];
				if ( !$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource ) ) {
					throw new Module_NoPermission;
				}
				if ( $article['published'] ) {
					$this->setTitle( sprintf( t('Edit Article "%1$s"'), $article['title'] ) );
				} else {
					$this->setTitle( sprintf( t('Edit Unpublished Article "%1$s"'), $article['title'] ) );
				}
				// Prepare form validation
				$form = new View_form( 'config/form_article_edit.html', 'article' );
				$form->addElement( 'article/cid', $article['cat_id'], t('Category'), new Validator_Int );
				$form->addElement( 'article/title', $article['title'], t('Title'), new Validator_Length(1, 255) );
				$form->addElement( 'article/published', $article['published'], t('Published'), new Validator_Bool );
				$form->setContentUrl( 'article/view/'.$article['clean_title'] );
				if ( $form->hasInput() && $form->isValid() ) {
					$fd = $form->getValues( 'article' );
					$this->_model()->editArticle( $article['id'], $fd['title'], $fd['published'], $fd['cid'] );
					$form->success( 'article/view/'.$article['clean_title'] );
					$this->_event->success( t('Edited article') );
					return zula_redirect( $this->_router->makeUrl( 'article', 'config', 'edit', null, array('id' => $article['id']) ) );
				} else {
					// Add additional data
					$form->assign( array(
										'ARTICLE_ID'	=> $article['id'],
										'CATEGORIES'	=> $this->_model()->getAllCategories(),
										'PARTS'			=> $this->_model()->getArticleParts( $article['id'] ),
										));
					return $form->getOutput();
				}
			} catch ( Router_ArgNoExist $e ) {
				$this->_event->error( t('No article selected') );
			} catch ( Article_NoExist $e ) {
				$this->_event->error( t('Article does not exist') );
			}
			return zula_redirect( $this->_router->makeUrl( 'article', 'config' ) );
		}

		/**
		 * Adds a new part to an existing article
		 *
		 * @return string
		 */
		public function addPartSection() {			
			$this->setTitle( t('Add Article Part') );
			$this->_locale->textDomain( $this->textDomain() );
			$this->setOutputType( self::_OT_CONFIG );
			if ( !$this->_acl->check( 'article_edit_article' ) ) {
				throw new Module_NoPermission;
			}
			// Get ID of the article that the part will be under
			try {
				$aid = $this->_router->getArgument( 'id' );
				$article = $this->_model()->getArticle( $aid );
				// Check user has permission to category
				$resource = 'article-cat-'.$article['cat_id'];
				if ( !$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource ) ) {
					throw new Module_NoPermission;
				}
				// Prepare form validation
				$form = $this->buildPartForm( null, null, 10, $aid );
				if ( $form->hasInput() && $form->isValid() ) {
					$fd = $form->getValues( 'article' );
					$this->_model()->addPart( $article['id'], $fd['body'], $fd['title'], $fd['order'] );
					$this->_event->success( t('Added Article Part') );
					return zula_redirect( $this->_router->makeUrl( 'article', 'config', 'edit', null, array('id' => $article['id']) ) );
				} else {
					return $form->getOutput();
				}
			} catch ( Router_ArgNoExist $e ) {
				$this->_event->error( t('No article selected') );
			} catch ( Article_NoExist $e ) {
				$this->_event->error( t('Article does not exist') );
			}
			return zula_redirect( $this->_router->makeUrl( 'article', 'config' ) );
		}
		
		/**
		 * Edit an article part, only if the article exists and user has
		 * permission to the parent category
		 *
		 * @return string|bool
		 */
		public function editPartSection() {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setOutputType( self::_OT_CONFIG );
			$this->setTitle( t('Edit Article Part') );
			if ( !$this->_acl->check( 'article_edit_article' ) ) {
				throw new Module_NoPermission;
			}
			// Get the article part ID that we need to edit
			try {
				$pid = $this->_router->getArgument( 'id' );
				$part = $this->_model()->getPart( $pid );
				$article = $this->_model()->getArticle( $part['article_id'] );
				// Check user has permission to the parent category
				$resource = 'article-cat-'.$article['cat_id'];
				if ( !$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource ) ) {
					throw new Module_NoPermission;
				}
				// Prepare form validation
				$form = $this->buildPartForm( $part['body'], $part['title'], $part['order'], $article['id'], $part['id'] );
				if ( $form->hasInput() && $form->isValid() ) {
					$fd = $form->getValues( 'article' );
					$this->_model()->editPart( $part['id'], $fd['title'], $fd['body'], $fd['order'] );
					$this->_event->success( t('Edited article part') );
					return zula_redirect( $this->_router->makeUrl( 'article', 'config', 'edit', null, array('id' => $article['id']) ) );
				} else {
					return $form->getOutput();
				}
			} catch ( Router_ArgNoExist $e ) {
				$this->_event->error( t('No article part selected') );
			} catch ( Article_PartNoExist $e ) {
				$this->_event->error( t('Article part does not exist') );
			} catch ( Article_NoExist $e ) {
				$this->_event->error( t('Article does not exist') );
			}
			return zula_redirect( $this->_router->makeUrl( 'article', 'config' ) );
		}
		
		/**
		 * Builds the form that allos users to add or edit an article part.
		 *
		 * @param string $body
		 * @param string $title
		 * @param int $order
		 * $param int $aid
		 * $param int $pid
		 * @return object
		 */
		protected function buildPartForm( $body=null, $title=null, $order=10, $aid=null, $pid=null ) {
			$op = is_null($pid) ? 'add' : 'edit';
			$form = new View_form( 'config/form_part.html', 'article', is_null($pid) );
			$args = array( 'id' => ($op == 'add' ? $aid : $pid) );
			$form->action( $this->_router->makeUrl( 'article', 'config', $op.'part', null, $args ) );
			$form->addElement( 'article/title', $title, t('Title'), new Validator_Length(0, 255) );
			$form->addElement( 'article/body', $body, t('Content'), new Validator_Length(1, 65535) );
			$form->addElement( 'article/order', $order, t('Order'), new Validator_Int );
			// Add additional form data
			$form->assign( array(
								'ID'			=> $pid,
								'ARTICLE_ID'	=> $aid,
								'OP'			=> $op,
								));
			return $form;
		}

		/**
		 * Deletes selected article parts - but you are unable to delete the last
		 * remaning part!
		 *
		 * @return bool
		 */
		public function deletePartSection() {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setOutputType( self::_OT_CONFIG );
			if ( !$this->_acl->check( 'article_edit_article' ) ) {
				throw new Module_NoPermission;
			}
			try {
				$article = $this->_model()->getArticle( $this->_input->post('article_id') );
				// Check user has permission to parent category
				$resource = 'article-cat-'.$article['cat_id'];
				if ( !$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource ) ) {
					throw new Module_NoPermission;
				}
				$articleParts = $this->_model()->getArticleParts( $article['id'] );
				$partCount = count( $articleParts );
				if ( $partCount == 1 ) {
					$this->_event->error( t('You can not delete the last remaining part') );
				} else {
					$delCount = 0;
					foreach( $this->_input->post( 'article_pids' ) as $pid ) {
						if ( $partCount - $delCount == 1 ) {
							break;
						} else if ( isset( $articleParts[ $pid ] ) ) {
							$this->_model()->deletePart( $pid );
							++$delCount;
						}
					}
					if ( $delCount ) {
						$this->_event->success( t('Deleted selected article parts') );
					}
				}
			} catch ( Input_KeyNoExist $e ) {
				$this->_event->error( t('No article parts selected') );
			} catch ( Article_NoExist $e ) {
				$this->_event->error( t('Article does not exist') );
			}
			if ( isset( $article['id'] ) ) {
				return zula_redirect( $this->_router->makeUrl( 'article', 'config', 'edit', null, array('id' => $article['id']) ) );
			} else {
				return zula_redirect( $this->_router->makeUrl( 'article', 'config' ) );
			}
		}

		/**
		 * Allows the user to change various settings for the article module.
		 *
		 * @return string|bool
		 */
		public function settingsSection() {
			$this->setTitle( t('Article Settings') );
			$this->_locale->textDomain( $this->textDomain() );
			$this->setOutputType( self::_OT_CONFIG );
			if ( !$this->_acl->check( 'article_manage_settings' ) ) {
				throw new Module_NoPermission;
			}
			// Check for needed post data
			if ( $this->_input->has( 'post', 'article/settings' ) ) {
				if ( $this->_input->checkToken() ) {
					foreach( $this->_input->post( 'article/settings' ) as $key=>$val ) {
						try {
							$this->_config_sql->update( 'article/'.$key, $val );
						} catch ( Config_KeyNoExist $e ) {
							$this->_event->error( $e->getMessage() );
						}
					}
					$this->_event->success( t('Updated Article Settings') );
				} else {
					$this->_event->error( Input::csrfMsg() );
				}
				return zula_redirect( $this->_router->makeUrl( 'article', 'config', 'settings' ) );
			} else {
				// Build up the form
				$html = new Html( 'article[settings][%s]' );
				$view = $this->loadView( 'config/settings.html' );
				$view->assignHtml( array(
										'S_PERPAGE'		=> $html->input( 'per_page', $this->_config->get('article/per_page') ),
										'S_HEADLINE'	=> $html->input( 'headline_limit', $this->_config->get('article/headline_limit') ),
										'S_JUMP_POS'	=> $html->select( 'jump_box_position', $this->_config->get('article/jump_box_position'),
																		  array( t('Top') => 'top', t('Bottom') => 'bottom' )
																		 ),
										'S_CAT_DESC'	=> $html->radio( 'show_cat_desc', $this->_config->get('article/show_cat_desc'),
																		 array( t('Yes') => true, t('No') => false )
																		),
										'CSRF'			=> $this->_input->createToken( true ),
										));
				return $view->getOutput();
			}
		}

	}

?>
