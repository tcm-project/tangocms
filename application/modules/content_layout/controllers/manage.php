<?php

/**
 * Zula Framework Module (content_layout)
 * --- Shows all of the template sectors and which modules are current
 * attached to it
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Content_layout
 */

	class Content_layout_controller_manage extends Zula_ControllerBase {

		/**
		 * Constructor
		 *
		 * @return object
		 */
		public function __construct( $moduleDetails, $config, $sector ) {
			parent::__construct( $moduleDetails, $config, $sector );
			$this->setPageLinks( array(
										t('Manage Layouts')	=> $this->_router->makeUrl( 'content_layout' ),
										t('Add Layout')		=> $this->_router->makeUrl( 'content_layout', 'index', 'add' ),
										));
		}

		/**
		 * Displays all controllers from the layout map, categories
		 * by which sector they are in.
		 *
		 * @param string $name
		 * @param array $args
		 * @return mixed
		 */
		public function __call( $name, $args ) {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setOutputType( self::_OT_CONFIG );
			if ( !$this->_acl->check( 'content_layout_config_module' ) ) {
				throw new Module_NoPermission;
			}
			$layoutName = substr($name, 0, -7);
			$this->_theme->addJsFile( 'jQuery/plugins/dnd.js' );
			$this->addAsset( 'js/dnd_order.js' );
			if ( empty( $layoutName ) ) {
				$this->_event->error( t('Unable to manage content layout, no layout given') );
				return zula_redirect( $this->_router->makeUrl( 'content_layout' ) );
			}
			$this->setTitle( sprintf( t('"%s" Content Layout'), $layoutName ) );
			$this->setOutputType( self::_OT_CONFIG );
			// Create the new content layout object
			$siteType = substr( $layoutName, 0, strpos($layoutName, '-') );
			$layout = new Theme_layout( $layoutName, Theme::getSiteTypeTheme( $siteType ) );
			// Build view form with validation for the regex (for layout)
			$form = new View_form( 'manage/main.html', 'content_layout' );
			$form->action( $this->_router->makeUrl( 'content_layout', 'manage', $layoutName ) );
			$form->addElement( 'content_layout/regex', $layout->getUrlRegex(), t('URL/Regex'), new Validator_Length(2, 255) );
			if ( $form->hasInput() && $form->isValid() ) {
				$layout->setUrlRegex( $form->getValues('content_layout/regex') );
				if ( $layout->save() ) {
					$this->_event->success( t('Updated content layout') );
					return zula_redirect( $this->_router->makeUrl( 'content_layout', 'manage', $layoutName ) );
				}
				$this->_event->error( t('Unable to save content layout') );
			}
			// Assign additional data
			$form->assign( array('LAYOUT' => $layout) );
			return $form->getOutput();
		}

		/**
		 * Creates a bridge between the Detaching Selected and Update Order
		 * functionaility, as there can only be one form with one action
		 *
		 * @return mixed
		 */
		public function bridgeSection() {
			$this->setOutputType( self::_OT_CONFIG );
			if ( !$this->_acl->check( 'content_layout_config_module' ) ) {
				throw new Module_NoPermission;
			}
			if ( !$this->_input->checkToken() ) {
				$this->_event->error( Input::csrfMsg() );
			} else if ( $this->_input->has( 'post', 'content_layout_detach' ) ) {
				$this->detachCntrlr();
			} else if ( $this->_input->has( 'post', 'content_layout_order' ) ) {
				$this->updateOrder();
			}
			try {
				return zula_redirect( $this->_router->makeUrl( 'content_layout', 'manage', $this->_input->post('content_layout_name') ) );
			} catch ( Input_KeyNoExist $e ) {
				return zula_redirect( $this->_router->makeUrl( 'content_layout' ) );
			}
		}

		/**
		 * Changes the order and placement of the controllers by
		 * post data that was submitted a long with the form
		 *
		 * @return bool
		 */
		protected function updateOrder() {
			try {
				$layoutName = $this->_input->post('content_layout_name');
				$siteType = substr( $layoutName, 0, strpos($layoutName, '-') );
				$layout = new Theme_Layout( $layoutName, Theme::getSiteTypeTheme( $siteType ) );
				// Update all of the controllers attributes
				$updated = 0;
				foreach( $this->_input->post( 'content_layout' ) as $cid=>$details ) {
					try {
						$cntrlr = $layout->getControllerDetails( $cid );
						$cntrlr['order'] = abs( $details['order'] );
						$cntrlr['sector'] = $details['sector'];
						$layout->editController( $cntrlr['id'], $cntrlr );
						$updated++;
					} catch ( Theme_Layout_ControllerNoExist $e ) {
					}
				}
				if ( $layout->save() ) {
					if ( $updated > 0 ) {
						$this->_event->success( sprintf( t('Updated Order and Placement for layout "%s"'), $layout->getName() ) );
					}
				} else {
					$this->_event->error( t('Unable to save layout, ensure file is writable') );
				}
			} catch ( Input_KeyNoExist $e ) {
			}
			return true;
		}

		/**
		 * Attempts to detach/remove a controller from the sector map for the
		 * correct site type. Done by controller ID. If the site type does not
		 * exist then it will not attempt to detach the controller.
		 *
		 * @return bool
		 */
		protected function detachCntrlr() {
			try {
				$layoutName = $this->_input->post('content_layout_name');
				$siteType = substr( $layoutName, 0, strpos( $layoutName, '-' ) );
				$layout = new Theme_Layout( $layoutName, Theme::getSiteTypeTheme( $siteType ) );
				$resources = array();
				$delCount = 0;
				foreach( $this->_input->post( 'controller_ids' ) as $cntrlrId ) {
					try {
						$layout->detachController( $cntrlrId );
						++$delCount;
						// Store resource IDs to delete
						$resources[] = 'layout_controller_'.$cntrlrId;
					} catch ( Theme_Layout_ControllerNoExist $e ) {
						$this->_event->error( sprintf( t('Unable to detach module ID "%d" as it does not exist'), $cntrlrId ) );
					}
				}
				if ( $layout->save() ) {
					// Remove ACL resources if needed
					if ( !empty( $resources ) ) {
						foreach( $resources as $tmpResource ) {
							try {
								$this->_acl->deleteResource( $tmpResource );
							} catch ( Acl_ResourceNoExist $e ) {
								$this->_log->message( 'Content Layout unable to remove ACL Resource "'.$tmpResource.'"', Log::L_INFO );
							}
						}
					}
					if ( $delCount > 0 ) {
						$this->_event->success( t('Detached selected modules') );
					}
				} else {
					$this->_event->error( t('Unable to save layout, ensure file is writable') );
				}
			} catch ( Input_KeyNoExist $e ) {
				$this->_event->error( t('No modules selected') );
			}
		}

	}

?>
