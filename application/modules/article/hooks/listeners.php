<?php

/**
 * Zula Framework Module (Article)
 * --- Hooks file for listning to possible events
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Robert Clipsham
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, 2009, Robert Clipsham
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Article
 */

	class Article_hooks extends Zula_HookBase {

		/**
		 * Constructor
		 * Calls the parent constructor to register the methods
		 *
		 * @return object
		 */
		public function __construct() {
			parent::__construct( $this );
		}

		/**
		 * Rss insert head
		 *
		 * @param string $module
		 * @param string $controller
		 * @param string $section
		 * @return array
		 */
		public function hookRssInsertHead( $module, $controller, $section ) {
			if ( $module == 'article' ) {
            	$feeds = array();
				if ( $controller == 'index' && Rss::feedExists( 'article-'.$section ) ) {
                	$feeds[] = 'article-'.$section;
				}
				if ( Rss::feedExists( 'article-latest' ) ) {
					$feeds[] = 'article-latest';
				}
				return $feeds;
			}
		}

		/**
		 * Makes details on what RSS feeds will be edited/added etc
		 * a long with the item details
		 *
		 * @param array $details	Article Details
		 * @param array $cat		Category Details
		 * @return array
		 */
		protected function makeDetails( $details, $cat ) {
			return array(
						'feeds'	=> array(
										'article-latest' => array(
																'title' => t('Latest articles', 'tangocms-article'),
																'desc'	=> sprintf( t('%s Latest articles'), $this->_config->get( 'config/title' ) ),
																'url'	=> $this->_router->makeFullUrl( '/article' ),
															),
										'article-'.$cat['clean_title']	=> array(
																				'title'	=> sprintf( t('Articles - %s', 'tangocms-article'), $cat['title'] ),
																				'desc'	=> sprintf( t('Latest %s articles', 'tangocms-article'), $cat['title'] ),
																				'url'	=> $this->_router->makeFullUrl( 'article/cat/'.$cat['clean_title'] ),
																		),
									),
						'item'	=> array(
										'id'	=> 'article-'.$details['id'],
										'title'	=> $details['title'],
										'url'	=> $this->_router->makeFullUrl( 'article/view/'.$details['clean_title'] ),
										'desc'	=> empty($details['part_body']) ? null : $details['part_body'],
										),
						);
		}


		/**
		 * Listen to own article adding, to add to RSS feed via RSS mod
		 *
		 * @param int 	$aid
		 * @param array $details
		 * @param array $cat	Article Category Details
		 * @return bool
		 */
		public function hookArticleAdd( $aid, $details, $cat ) {
			if ( $details['published'] == 1  ) {
				$details['id'] = $aid;
				$feedDetails = $this->makeDetails( $details, $cat );
				Hooks::notifyAll( 'rssmod_add', $feedDetails['feeds'], $feedDetails['item'] );
			}
		}

		/**
		 * Edits an item title (not the body, since that is when editing an article part
		 *
		 * @param int  $aid
		 * @param array $details
		 * @return bool
		 */
		public function hookArticleEdit( $aid, $details ) {
			try {
				$cat = $this->_model()->getCategory( $details['cid'] );
				$feedDetails = $this->makeDetails( $details, $cat );
				if ( $details['published'] == 1 ) {
					Hooks::notifyAll( 'rssmod_edit', $feedDetails['feeds'], $feedDetails['item'] );
					$parts = $this->_model()->getArticleParts( $aid );
					$part = reset( $parts );
					$this->hookArticleEditPart( $part['id'], $part, $cat );
				} else {
					Hooks::notifyAll( 'rssmod_delete', array_keys($feedDetails['feeds']), 'article-'.$aid );
				}
			} catch ( Article_CatNoExist $e ) {
				return false;
			}
		}

		/**
		 * Delete an item from RSS feed via RSS module
		 *
		 * @param int  $aid
		 * @param array $details
		 * @return bool
		 */
		public function hookArticleDelete( $aid, $details ) {
			if ( $details['published'] == 1 ) {
				try {
					$cat = $this->_model()->getCategory( $details['cat_id'] );
					$feedDetails = $this->makeDetails( $details, $cat );
					Hooks::notifyAll( 'rssmod_delete', array_keys($feedDetails['feeds']), 'article-'.$aid );
				} catch ( Article_CatNoExist $e ) {
					return false;
				}
			}
		}

		/**
		 * Updates an article feed item body, from the article part
		 *
		 * @param int $pid
		 * @param array $partDetails
		 * @return bool
		 */
		public function hookArticleEditPart( $pid, $partDetails ) {
			if ( $partDetails['order'] == 1 ) {
				try {
					$article = $this->_model()->getArticle( $partDetails['article_id'] );
					$cat = $this->_model()->getCategory( $article['cat_id'] );
					$item = array(
								'id'	=> 'article-'.$partDetails['article_id'],
								'title'	=> null,
								'url'	=> null,
								'desc'	=> $partDetails['body']
								);
					Hooks::notifyAll( 'rssmod_edit', array('article-latest', 'article-'.$cat['clean_title']), $item );
				} catch ( Article_CatNoExist $e ) {
					return false;
				} catch ( Article_NoExist $e ) {
					return false;
				}
			}
		}

		/**
		 * Hook: router_pre_parse
		 * Rewrites the URL to the index controller, instead of cat
		 *
		 * @return string
		 */
		public function hookRouterPreParse( $url ) {
			if ( preg_match( '#^(admin/)?article/cat/(.*?)$#i', $url, $matches ) ) {
				return $matches[1].'article/index/'.$matches[2];
			} else {
				return $url;
			}
		}

		/**
		 * Hook: article_display_modes
		 * Gets all display modes that this module has
		 *
		 * @return array
		 */
		public function hookArticleDisplayModes() {
			return array(
						'singlecategory'	=> t('Single category', _PROJECT_ID.'-article'),
						'headlines'			=> t('Headlines', _PROJECT_ID.'-article'),
						'categories'		=> t('Category list', _PROJECT_ID.'-article'),
						'allarticles'		=> t('All articles', _PROJECT_ID.'-article')
						);
		}

		/**
		 * Hook: article_resolve_mode
		 * Resolves a given Controller, Section and config data to an
		 * avaible display mode offered.
		 *
		 * @param string $cntrlr
		 * @param string $sec
		 * @param array $config
		 * @return string
		 */
		public function hookArticleResolveMode( $cntrlr, $sec, $config ) {
			switch( $cntrlr ) {
				case 'headlines':
					return 'headlines';

				case 'categories':
					return 'categories';

				case 'index':
				default:
					return $sec == 'index' ? 'allarticles' : 'singlecategory';
			}
		}

		/**
		 * Hook: article_display_mode_config
		 * Returns HTML (commonly a table) to configure a display mode
		 *
		 * @param string $mode
		 * @return string
		 */
		public function hookArticleDisplayModeConfig( $mode ) {
			switch( $mode ) {
				case 'headlines':
					try {
						$headlineLimit = $this->_input->post( 'headline_limit' );
					} catch ( Input_KeyNoExist $e ) {
						$headlineLimit = $this->_config->get( 'article/headline_limit' );
					}
					$view = new View( 'layout_edit/headlines.html', 'article' );
					$view->assign( array(
										'CATEGORIES'	=> $this->_model( 'article', 'article' )->getAllCategories(),
										'LIMIT'			=> $headlineLimit,
										));
					break;

				case 'singlecategory':
					$view = new View( 'layout_edit/singlecat.html', 'article' );
					$view->assign( array(
										'CATEGORIES'	=> $this->_model( 'article', 'article' )->getAllCategories(),
										));
					break;

				case 'categories':
					$view = new View( 'layout_edit/categories.html', 'article' );
					break;

				case 'allarticles':
					$view = new View( 'layout_edit/all_articles.html', 'article' );
					break;
			}
			return $view->getOutput();
		}

	}
?>
