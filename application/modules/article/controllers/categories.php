<?php

/**
 * Zula Framework Module (Article)
 * --- Displays all categories the user has permission to and
 * how many articles are in that category
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Article
 */

	class Article_controller_categories extends Zula_ControllerBase {

		/**
		 * index_section() display every category with the number of articles
		 * @return string
		 */
		public function indexSection() {
			$this->_i18n->textDomain( $this->textDomain() );
			$this->setTitle( t('Categories') );
			$this->setOutputType( self::_OT_CONTENT_INDEX );
			/**
			 * Get all categories and find out how many articles it has
			 */
			$categories = $this->_model()->getAllCategories();
			if ( empty( $categories ) ) {
				return '<p>'.t('No article categories to display.').'</p>';
			} else {
				foreach( $categories as &$category ) {
					$category['count'] = $this->_model()->countArticles( $category['id'] );
				}
				$view = $this->loadView( 'categories/list.html' );
				$view->assign( array('CATEGORIES' => $categories) );
				return $view->getOutput();
			}
		}

	}

?>
