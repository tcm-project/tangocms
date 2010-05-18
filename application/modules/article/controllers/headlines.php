<?php

/**
 * Zula Framework Module (Article)
 * --- Displays the latest article headlines for a category or
 * all categories.
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Article
 */

	class Article_controller_headlines extends Zula_ControllerBase {

		/**
		 * Displays the latest headlines/titles of articles for a certain
		 * category or every category available
		 *
		 * @return string
		 */
		public function indexSection() {
			$this->_i18n->textDomain( $this->textDomain() );
			$this->setTitle( t('Article Headlines') );
			$this->setOutputType( self::_OT_CONTENT_INDEX );
			/**
			 * Get which category to display, if any
			 */
			try {
				$displayCat = abs( $this->_config->get('article/display_cat') );
				if ( $displayCat == 'all' ) {
					$displayCat = null;
				}
			} catch ( Config_KeyNoExist $e ) {
				if ( $this->inSector( 'SC' ) && $this->_router->hasArgument( 'cat' ) ) {
					$displayCat = abs( $this->_router->getArgument( 'cat' ) );
				} else {
					$displayCat = null;
				}
			}
			// How many headlines to display
			try {
				$limit = abs( $this->_config->get('article/headline_limit') );
			} catch ( Config_KeyNoExist $e ) {
				$limit = 5;
			}
			if ( $displayCat ) {
				try {
					$category = $this->_model()->getCategory( $displayCat );
					$this->setTitle( sprintf( t('%s Headlines'), $category['title'] ) );
					$resource = 'article-cat-'.$category['id'];
					if ( !$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource ) ) {
						throw new Module_NoPermission;
					}
				} catch ( Article_CatNoExist $e ) {
					throw new Module_ControllerNoExist;
				}
			}
			// Gather all articles required
			$articles = $this->_model()->getAllArticles( $limit, 0, $displayCat );
			$articleCount = $this->_model()->getCount();
			$view = $this->loadView( 'headline/headline.html' );
			$view->assign( array(
								'ARTICLES'		=> $articles,
								'ARTICLE_COUNT'	=> $articleCount,
								'CATEGORY'		=> isset($category) ? $category : null,
								));
			return $view->getOutput();
		}

	}

?>
