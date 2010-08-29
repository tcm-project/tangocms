<?php

/**
 * Zula Framework Module (article)
 * --- Publish articles which can have multiple parts
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Article
 */

	require_once 'base.php';

	class Article_controller_index extends ArticleBase {

		/**
		 * Magic call function allows for shorter URLs, it will be in the format
		 * of /article/index/clean-title and will then only show one category
		 * instead of all of them
		 *
		 * @param string $name
		 * @param array $args
		 * @return mixed
		 */
		public function __call( $name, $args ) {
			return $this->displayArticles( substr($name, 0, -7) );
		}

		/**
		 * Displays articles from every category
		 *
		 * @return string
		 */
		public function indexSection() {
			try {
				$catSelector = (bool) $this->_config->get( 'article/display_cat_selector' );
			} catch ( Config_KeyNoExist $e ) {
				$catSelector = true;
			}
			return $this->displayArticles( false, $catSelector );
		}

		/**
		 * Displays published articles for a given category (if any)
		 *
		 * @param string $category
		 * @param bool $catSelector	Toggles the category selector
		 * @return string
		 */
		protected function displayArticles( $category=false, $catSelector=true ) {
			$this->setOutputType( self::_OT_CONTENT_INDEX );
			if ( empty( $category ) ) {
				$this->setTitle( t('Latest articles') );
				$categories = $this->_model()->getAllCategories();
				$cid = null;
			} else {
				/**
				 * Attempt to get the single article category details, and check permission
				 */
				try {
					$category = $this->_model()->getCategory( $category, false );
					$this->setTitle( $category['title'] );
					$categories = array($category['id'] => $category);
					$cid = $category['id'];
					$resource = 'article-cat-'.$category['id'];
					if ( !$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource ) ) {
						throw new Module_NoPermission;
					}
				} catch ( Article_CatNoExist $e ) {
					throw new Module_ControllerNoExist;
				}
			}
			/**
			 * Check how many to display per page, and what page we are on
			 */
			try {
				$perPage = abs( $this->_config->get('article/per_page') );
			} catch ( Input_KeyNoExist $e ) {
				$perPage = 12;
			}
			if ( $this->inSector('SC') && $this->_input->has( 'get', 'page' ) ) {
				$curPage = abs( $this->_input->get( 'page' )-1 );
			} else {
				$curPage = 0;
			}
			// Get the required articles and parse their first article part body
			$articles = array();
			foreach( $this->_model()->getAllArticles( $perPage, $curPage*$perPage, $cid ) as $tmpArticle ) {
				if ( isset( $categories[ $tmpArticle['cat_id'] ] ) ) {
					$parts = $this->_model()->getArticleParts( $tmpArticle['id'] );
					$firstPart = current( $parts );
					$editor = new Editor( $firstPart['body'] );
					$editor->setContentUrl( $this->_router->makeUrl( 'article', 'view', $tmpArticle['clean_title'] ) );
					$tmpArticle['body'] = $editor->parse( true );
					$tmpArticle['category_title'] = $categories[ $tmpArticle['cat_id'] ]['title'];
					$tmpArticle['category_clean_title'] = $categories[ $tmpArticle['cat_id'] ]['clean_title'];
					$articles[] = $tmpArticle;
				}
			}
			$articleCount = $this->_model()->getCount();
			if ( $articleCount > 0 ) {
				$pagination = new Pagination( $articleCount, $perPage );
			}
			// Build up the view
			$view = $this->loadView( 'index/latest.html' );
			$view->assign( array(
								'META_FORMAT'	=> $this->getMetaFormat( $this->_config->get('article/meta_format') ),
								'CAT_DETAILS'	=> $cid ? $category : null,
								));
			$view->assignHtml( array(
									'ARTICLES'		=> $articles,
									'PAGINATION'	=> isset($pagination) ? $pagination->build() : null,
									));
			if ( $cid == false && $catSelector ) {
				/** Prepend the category selector */
				$catSelectorView = $this->loadView( 'index/category_selector.html' );
				$catSelectorView->assign( array('CATEGORIES' => $categories) );
				return $catSelectorView->getOutput().$view->getOutput( true );
			} else {
				return $view->getOutput( true );
			}
		}

	}

?>
