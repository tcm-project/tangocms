<?php

/**
 * Zula Framework Module (Page)
 * --- Displays a user-created page
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Page
 */

	class Page_controller_index extends Zula_ControllerBase {

		/**
		 * Magic method - allows for shorter URL's eg: /page/index/page-title
		 *
		 * @param string $name
		 * @param array $args
		 * @return string
		 */
		public function __call( $name, $args ) {
			return $this->displayPage( substr($name, 0, -7) );
		}

		/**
		 * Builds up the correct view for the requested page to be displayed, if it exists.
		 *
		 * @param string $cleanTitle
		 * @param bool $import			Toogle if page is an import into main page.
		 * @return string|bool
		 */
		protected function displayPage( $cleanTitle, $import=false ) {
			$this->setOutputType( self::_OT_CONTENT_STATIC );
			$this->_locale->textDomain( $this->textDomain() );
			try {
				$page = $this->_model()->getPage( $cleanTitle, false );
				if ( $import === false ) {
					$this->setTitle( $page['title'] );
				}
				$resource = 'page-'.$page['id'];
				if ( !$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource ) ) {
					if ( $import === false ) {
						throw new Module_NoPermission;
					} else {
						return false;
					}
				}
			} catch ( Page_NoExist $e ) {
				if ( $import === false ) {
					throw new Module_ControllerNoExist;
				} else {
					$this->_log->message( 'Page unable to import page "'.$page.'" as it does not exist', Log::L_WARNING );
					return false;
				}
			}
			if ( $import === true ) {
				return $page['body'];
			} else {
				/**
				* Check if the Quick Links need to be added
				*/
				if ( $this->inSector( 'SC' ) && $import === false ) {
					$args = array('id' => $page['id'], 'qe' => 'true');
					if ( $this->_acl->check( 'page_edit' ) ) {
						$this->setPageLinks( array(t('Edit Page') => $this->_router->makeUrl( 'page', 'config', 'edit', null, $args )) );
					}
					if ( $this->_acl->check( 'page_delete' ) ) {
						$url = $this->_router->makeUrl( 'page', 'config', 'delete', null, $args )
											 ->queryArgs( array('zct' => $this->_input->createToken()) );
						$this->setPageLinks( array(t('Delete Page') => $url) );
					}
				}
				/**
				 * Generate the Next/Previous links for sub-pages
				 */
				$nodePath = $this->_model()->findPath( $page['id'] );
				$links = $this->generateLinks( $page['id'], $this->_model()->getChildren( $nodePath[0]['id'], true ) );
				if ( !isset( $links['previous'] ) ) {
					$links['previous'] = $nodePath[0];
				}
				// Load view and build the body
				$view = $this->loadView( 'index/page.html' );
				$editor = new Editor( $page['body'] );
				$body = $editor->parse().$this->makePageIndex( $page['id'] );
				unset( $page['body'] ); # Not needed any more
				$view->assignHtml( array(
										'BODY' => preg_replace_callback( '#{%import:(.*?)%}#', array($this, 'pageImport'), $body ),
										));
				$view->assign( array(
									'PAGE'	=> $page,
									'PATH'	=> $nodePath,
									'LINKS'	=> $links,
									));
				return $view->getOutput( true );
			}
		}

		/**
		 * Handles page imports using {%import:clean_title%}
		 * syntax.
		 *
		 * @param array $matches
		 * @return string|bool
		 */
		protected function pageImport( $matches ) {
			try {
				$editor = new Editor( $this->displayPage($matches[1], true) );
				$body = $editor->parse();
				if ( strpos( $body, '<p>' ) === 0 ) {
					$body = substr( $body, 3 );
				}
				if ( strrpos( $body, '</p>' ) === strlen($body)-4 ) {
					$body = substr( $body, 0, -4 );
				}
				return $body.$this->makePageIndex( $matches[1] );
			} catch ( Page_NoExist $e ) {
				$this->_log->message( 'Page could not import page "'.$matches[1].'" as it does not exist', Log::L_WARNING );
				return false;
			} catch ( Module_NoPermission $e ) {
				return false;
			}
		}

		/**
		 * Generates an array containing the Previous and Next links/titles etc
		 *
		 * @param int $pid
		 * @param array $children	Children of the provided Page ID
		 * @return array
		 */
		protected function generateLinks( $pid, array $children ) {
			$links = array();
			foreach( $children as $key=>$child ) {
				if ( $child['id'] == $pid ) {
					if ( isset( $children[ $key-1 ] ) ) {
						$links['previous'] = $children[ $key-1 ];
					}
					if ( isset( $children[ $key+1 ] ) ) {
						$links['next'] = $children[ $key+1 ];
					}
				}
			}
			return $links;
		}

		/**
		 * Makes the index/contents page for a page.
		 *
		 * This is similar to what is found at the start of a book.
		 * It will create a string in Wiki syntax, as it's the
		 * eaasiest way for creating this list.
		 *
		 * @param int $pid
		 * @return string
		 */
		protected function makePageIndex( $pid ) {
			$children = $this->_model()->getChildren( $pid, true );
			if ( !empty( $children ) ) {
				$wikiPage = "#!mediawiki\n===".t('Table of Contents')."===\n";
				foreach( $children as $child ) {
					$pageLink = $this->_router->makeUrl( 'page', 'index', $child['clean_title'] );
					$wikiPage .= str_repeat( '#', $child['depth']+1 ).'[['.$pageLink.'|'.$child['title'].']]'."\n";
				}
				$editor = new Editor( $wikiPage );
				return $editor->parse();
			} else {
				return '';
			}
		}

	}

?>
