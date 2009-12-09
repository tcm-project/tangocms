<?php
// $Id: listeners.php 2797 2009-11-23 22:00:46Z alexc $

/**
 * Zula Framework Module (media)
 * --- Hooks file for listning to possible events
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_media
 */

	class Media_hooks extends Zula_HookBase {

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
		 * Hook: router_pre_parse
		 * Rewrites  the URL to the index controller, instead of cat
		 *
		 * @return string
		 */
		public function hookRouterPreParse( $url ) {
			if ( preg_match( '#^(admin/)?media/cat/(.*?)$#i', $url, $matches ) ) {
				return $matches[1].'media/index/cat/name/'.$matches[2];
			} else {
				return $url;
			}
		}

		/**
		 * Hook: media_display_modes
		 * Gets all display modes that this module has
		 *
		 * @return array
		 */
		public function hookMediaDisplayModes() {
			return array(
						'categorylist'	=> t('Category List & Latest Media', _PROJECT_ID.'-media'),						
						'category'		=> t('Media Category', _PROJECT_ID.'-media'),
						'item'			=> t('Media Item', _PROJECT_ID.'-media'),
						);
		}

		/**
		 * Hook: media_resolve_mode
		 * Resolves a given Controller, Section and config data to an
		 * avaible display mode offered.
		 *
		 * @param string $cntrlr
		 * @param string $sec
		 * @param array $config
		 * @return string
		 */
		public function hookMediaResolveMode( $cntrlr, $sec, $config ) {
			if ( $cntrlr == 'view' ) {
				return 'item';
			} else if ( $sec == 'cat' ) {
				return 'category';
			} else {
				return 'categoryList';
			}
		}

		/**
		 * Hook: media_display_mode_config
		 * Returns HTML (commonly a table) to configure a display mode
		 *
		 * @param string $mode
		 * @return string
		 */
		public function hookMediaDisplayModeConfig( $mode ) {
			switch ( $mode ) {
				case 'categorylist':
					$view = new View( 'layout_edit/index.html', 'media' );
					break;

				case 'category':
					try {
						$showDetails = (bool) $this->_input->post( 'show_item_details' );
					} catch ( Input_KeyNoExist $e ) {
						$showDetails = true;
					}
					$view = new View( 'layout_edit/category.html', 'media' );
					$view->assign( array(
										'CATEGORIES'	=> $this->_model()->getAllCategories(),
										'SHOW_DETAILS'	=> $showDetails,
										));
					break;

				case 'item':
					try {
						$item = $this->_input->post( 'sec' );
					} catch ( Input_KeyNoExist $e ) {
						$item = '';
					}
					$view = new View( 'layout_edit/item.html', 'media' );
					$view->assign( array('ITEM' => $item) );
			}
			try {
				$perPage = $this->_input->post( 'per_page' );
			} catch ( Input_KeyNoExist $e ) {
				$perPage = $this->_config->get( 'media/per_page' );
			}
			try {
				$lightbox = (bool) $this->_input->post( 'use_lightbox' );
			} catch ( Input_KeyNoExist $e ) {
				$lightbox = false;
			}
			$view->assign( array(
								'PER_PAGE'	=> abs($perPage),
								'LIGHTBOX'	=> $lightbox,
								));
			return $view->getOutput();
		}

	}

?>
