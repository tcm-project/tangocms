<?php

/**
 * Zula Framework Module
 * Shows an article and its parts
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Article
 */

	require_once 'base.php';

	class Article_controller_view extends ArticleBase {

		/**
		 * Magic method - allows for shorter URL's eg:
		 * /article/view/clean-title
		 *
		 * @param string $name
		 * @param array $args
		 * @return string
		 */
		public function __call( $name, $args ) {
			$this->setOutputType( self::_OT_CONTENT_DYNAMIC );
			try {
				$article = $this->_model()->getArticle( substr($name, 0, -7), false );
				$this->setTitle( $article['title'] );
				$category = $this->_model()->getCategory( $article['cat_id'] );
				// Check permission to parent category
				$resource = 'article-cat-'.$category['id'];
				if ( !$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource ) ) {
					throw new Module_NoPermission;
				} else if ( !$article['published'] ) {
					throw new Module_ControllerNoExist;
				}
				/**
				 * Gather all parts for this article, and check the requested part actually exists
				 */
				try {
					$part = abs( $this->_input->get('part') );
				} catch ( Input_KeyNoExist $e ) {
					$part = 0;
				}
				$articleParts = array_values( $this->_model()->getArticleParts( $article['id'], false ) ); # Done to reindex array
				try {
					// Get details for the correct part
					if ( empty( $part ) ) {
						$partId = $articleParts[0]['id'];
					} else if ( isset( $articleParts[ $part-1 ]['id'] ) ) {
						$partId = $articleParts[ $part-1 ]['id'];
					} else {
						throw new Article_PartNoExist( $part );
					}
					$requestedPart = $this->_model()->getPart( $partId );
					$editor = new Editor( $requestedPart['body'] );
					$body = $editor->parse();
				} catch ( Article_PartNoExist $e ) {
					throw new Module_ControllerNoExist;
				}
				/**
				 * Build up pagination and the main view file
				 */
				try {
					$curPage = abs( $this->_input->get( 'part' )-1 );
				} catch ( Input_KeyNoExist $e ) {
					$curPage = 0;
				}
				$pagination = new Pagination( count($articleParts), 1, 'part' );
				if ( count($articleParts) > 1 ) {
					$this->addAsset( 'js/jumpbox.js' );
				}
				$view = $this->loadView( 'view/article.html' );
				$view->assign( array(
									'META_FORMAT'	=> $this->getMetaFormat( $this->_config->get('article/meta_format') ),
									'ARTICLE'		=> $article,
									'REQUESTED_PART'=> $requestedPart,
									'ARTICLE_PARTS'	=> $articleParts,
									'CATEGORY'		=> $category,
									));
				$view->assignHtml( array(
										'BODY'			=> $body,
										'PAGINATION'	=> $pagination->build(),
										));
				return $view->getOutput( true );
			} catch ( Article_NoExist $e ) {
				throw new Module_ControllerNoExist;
			}
		}

	}

?>
