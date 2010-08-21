<?php

/**
 * Zula Framework Module
 * --- Displays latest media from all categories, or a specific category
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Media
 */

	class Media_controller_index extends Zula_ControllerBase {

		/**
		 * Displays the latest media from all categories, and displays
		 * a simple category selector.
		 *
		 * @return string
		 */
		public function indexSection() {
			$this->setTitle( t('Media') );
			// Build main view and output with the latest media
			$view = $this->loadView( 'index/cat_selector.html' );
			$view->assign( array('CATEGORIES' => $this->_model()->getAllCategories()) );
			return $view->getOutput().'<h3>'.t('Latest Media').'</h3>'.$this->buildLatest();
		}

		/**
		 * Identical to the index section, however no category selector
		 *
		 * @return string
		 */
		public function latestSection() {
			$this->setTitle( t('Latest media') );
			return $this->buildLatest();
		}

		/**
		 * Displays latest media items for a single category
		 *
		 * @return mixed
		 */
		public function catSection() {
			if ( $this->inSector( 'SC' ) && $this->_router->hasArgument( 'name' ) ) {
				$category = $this->_router->getArgument( 'name' );
			} else if ( $this->_config->has( 'media/display_cat' ) ) {
				$category = $this->_config->get( 'media/display_cat' );
			} else {
				throw new Module_ControllerNoExist;
			}
			return $this->buildLatest( $category );
		}

		/**
		 * Builds the view to display latest media items from all
		 * categories, or a specific category.
		 *
		 * @param string $category
		 * @return string
		 */
		protected function buildLatest( $category=null ) {
			if ( $category == null ) {
				$cid = null;
			} else {
				try {
					$category = $this->_model()->getCategory( $category, false );
					$cid = $category['id'];
					$resource = 'media-cat_view_'.$category['id'];
					if ( !$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource ) ) {
						throw new Module_NoPermission;
					}
					$this->setTitle( $category['name'] );
					// Check if the user can upload/add items to this category
					$moderateAcl = $this->_acl->check( 'media-cat_moderate_'.$cid );
					$uploadAcl = $this->_acl->check( 'media-cat_upload_'.$cid );
					if ( $moderateAcl || $uploadAcl ) {
						$this->setPageLinks( array(
													t('Upload media item')		=> $this->_router->makeUrl('media', 'add', 'upload')->queryArgs(array('cid' => $cid)),
													t('Add external media item')=> $this->_router->makeUrl('media', 'add', 'external')->queryArgs(array('cid' => $cid)),
													));
					}
				} catch ( Media_CategoryNoExist $e ) {
					throw new Module_ControllerNoExist;
				}
			}
			// Check if details for the items should be displayed
			try {
				$showDetails = (bool) $this->_config->get('media/show_item_details');
			} catch ( Config_KeyNoExist $e ) {
				$showDetails = true;
			}
			// Get number of latest media items to display, and which page we are on
			$perPage = abs( $this->_config->get('media/per_page') );
			if ( $this->inSector( 'SC' ) && $this->_input->has( 'get', 'page' ) ) {
				$curPage = abs( $this->_input->get('page')-1 );
			} else {
				$curPage = 0;
			}
			$items = $this->_model()->getItems( $perPage, ($perPage*$curPage), $cid );
			$itemCount = $this->_model()->getItemCount();
			if ( $itemCount ) {
				$pagination = new Pagination( $itemCount, $perPage );
			}
			$view = $this->loadView( 'index/latest.html' );
			$view->assign( array(
								'ITEMS'			=> $items,
								'CATEGORY'		=> $category,
								'SHOW_DETAILS'	=> $showDetails,
								));
			$view->assignHtml( array(
									'PAGINATION'	=> isset($pagination) ? $pagination->build() : '',
									));
			// Check if lightbox effect needs to be used
			if ( $this->_config->get( 'media/use_lightbox' ) ) {
				$this->_theme->addJsFile( 'jquery.tangobox' );
				$this->_theme->addCssFile( 'jquery.tangobox.css' );
				$view->assign( array('LIGHTBOX' => true) );
			} else {
				$view->assign( array('LIGHTBOX' => false) );
			}
			return $view->getOutput();
		}

	}

?>
