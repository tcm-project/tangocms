<?php
// $Id: index.php 2768 2009-11-13 18:12:34Z alexc $

/**
 * Zula Framework Module (menu)
 * -- Provides a powerful menu that can be used anywhere to display links
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Menu
 */

	class Menu_controller_index extends Zula_ControllerBase {

		/**
		 * Gets the correct category that needs to be displayed, if any
		 *
		 * @return string
		 */
		public function indexSection() {
			$this->_locale->textDomain( $this->textDomain() );
			try {
				$cid = $this->_config->get( 'menu/display_category' );
			} catch ( Config_KeyNoExist $e ) {
				if ( $this->inSector( 'SC' ) && $this->_router->hasArgument( 'id' ) ) {
					$cid = $this->_router->getArgument( 'id' );
				} else {
					throw new Module_ControllerNoExist;
				}
			}
			/**
			 * Ensure category exists and attempt to construct the menu
			 */
			try {
				$category = $this->_model()->getCategory( $cid );
				$this->setTitle( $category['name'] );
				// Check permission
				$aclResource = 'menu-cat-'.$cid;
				if ( !$this->_acl->resourceExists( $aclResource ) || !$this->_acl->check( $aclResource ) ) {
					throw new Module_NoPermission;
				}
			} catch ( Menu_CategoryNoExist $e ) {
				if ( $this->inSector( 'SC' ) ) {
					throw new Module_ControllerNoExist;
				} else {
					return false;
				}
			}
			// Construct the menu
			$menuItems = $this->_model()->getallItems( $cid );
			if ( empty( $menuItems ) ) {
				$output = '<p>'.t('There are no menu items to be displayed').'</p>';
			} else {
				$view = $this->loadView( 'index/main.html' );
				$view->assign( array('CAT_DETAILS' => $category) );
				$view->assignHtml( array('MENU_ITEMS' => $this->buildItems( $menuItems )) );
				$output = $view->getOutput();
			}
			return $output;
		}

		/**
		 * Builds the list items (<li>) elements for a menu category
		 * with the provided items, recursively if need be.
		 *
		 * @param array $items
		 * @return string
		 */
		protected function buildItems( array $items ) {
			$curUrl = $this->_router->getRawRequestPath();
			$list = '<ul class="menu-category">'."\n\t";
			foreach( $items as $item ) {
				$item['url'] = ltrim( $item['url'], '/' );
				if ( (strtoupper($item['url']) == '[FRONT_PAGE]' || !trim($item['url'], '/') ) && !$curUrl || $item['url'] == $curUrl ) {
					$class = 'class="menu-current"';
				} else {
					$class = null;
				}
				// Create the correct URL for the menu item to use
				if ( $item['url'] == 'admin' ) {
					$item['url'] = $this->_router->makeUrl( '', '', '', 'admin' );
				} else if ( strtoupper( $item['url'] ) == '[FRONT_PAGE]' || !trim( $item['url'] ) ) {
					$item['url'] = $this->_router->makeUrl( '', '', '', 'main' );
				} else if ( strpos( $item['url'], 'www.' ) === 0 ) {
					$item['url'] = 'http://'.$item['url'];
				} else if ( !zula_url_has_scheme( $item['url'] ) ) {
					if ( $item['url'][0] == '#' ) {
						$item['url'] = $this->_router->makeUrl( $this->_router->getRawRequestPath() ).$item['url'];
					} else {
						$item['url'] = $this->_router->makeUrl( $item['url'] );
					}
				}
				// Gather children and append the list item
				$children = empty($item['children']) ? '' : $this->buildItems( $item['children'] );
				$list .= sprintf( '<li id="menu-item-%1$s" %2$s><a href="%3$s" title="%4$s">%5$s</a>%6$s</li>'."\n",
								  $item['id'],
								  $class,
								  $item['url'],
								  zula_htmlspecialchars( ($item['attr_title'] ? $item['attr_title'] : $item['name']) ),
								  zula_htmlspecialchars( $item['name'] ),
								  $children
								);
			}
			return $list.'</ul>';
		}
		
	}

?>
