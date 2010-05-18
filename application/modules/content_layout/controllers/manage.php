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
			$this->_i18n->textDomain( $this->textDomain() );
			$this->setOutputType( self::_OT_CONFIG );
			if ( !$this->_acl->check( 'content_layout_config_module' ) ) {
				throw new Module_NoPermission;
			}
			$layoutName = substr( $name, 0, -7 );
			$siteType = substr( $layoutName, 0, strpos($layoutName, '-') );
			if ( empty( $layoutName ) || !$this->_router->siteTypeExists( $siteType ) ) {
				$this->_event->error( t('Unable to manage content layout, invalid name given') );
				return zula_redirect( $this->_router->makeUrl( 'content_layout' ) );
			}
			$this->setTitle( sprintf( t('"%s" Content Layout'), $layoutName ) );
			$this->setOutputType( self::_OT_CONFIG );
			// Create the new content layout object
			$layout = new Layout( $layoutName );
			if ( !$layout->exists() ) {
				$this->_event->error( t('Provided layout does not exist') );
				return zula_redirect( $this->_router->makeUrl( 'content_layout' ) );
			}
			// Build view form with validation for the regex (for layout)
			$form = new View_form( 'manage/main.html', 'content_layout' );
			$form->caseSensitive();
			$form->action( $this->_router->makeUrl( 'content_layout', 'manage', $layoutName ) );
			$form->addElement( 'content_layout/regex', $layout->getRegex(), t('URL/Regex'), new Validator_Length(2, 255) );
			if ( $form->hasInput() && $form->isValid() ) {
				$layout->setRegex( $form->getValues('content_layout/regex') );
				if ( $layout->save() ) {
					$this->_event->success( t('Updated content layout') );
					return zula_redirect( $this->_router->makeUrl( 'content_layout', 'manage', $layoutName ) );
				}
				$this->_event->error( t('Unable to save content layout') );
			}
			/**
			 * Gather all controllers in the layout for the theme of the site type
			 * this layout is for.
			 */
			$theme = new Theme( $this->_config->get('theme/'.$siteType.'_default') );
			$themeSectors = array();
			foreach( $theme->getSectors() as $sector ) {
				$themeSectors[ $sector['id'] ] = array(
										'sector'	=> $sector,
										'cntrlrs'	=> $layout->getControllers( $sector['id'] ),
										);
			}
			// Assign additional data
			$form->assign( array(
								'layoutName'	=> $layout->getName(),
								'themeSectors'	=> $themeSectors,
								));
			$this->_theme->addJsFile( 'jQuery/plugins/dnd.js' );
			$this->addAsset( 'js/dnd_order.js' );
			return $form->getOutput();
		}

		/**
		 * Updates which module to use in the FPSC layout (module used in the homepage)
		 *
		 * @return bool
		 */
		public function fpscSection() {
			try {
				$siteType = $this->_input->post( 'content_layout/siteType' );
				$module = $this->_input->post( 'content_layout/module' );
				if ( $this->_router->siteTypeExists( $siteType ) ) {
					$layout = new Layout( 'fpsc-'.$siteType );
					$fpscCntrlr = $layout->getControllers( 'SC' );
					$fpscCntrlr = reset( $fpscCntrlr );
					if ( $module != $fpscCntrlr['mod'] ) {
						// User is changing the module, remove and add new
						$layout->detachController( $fpscCntrlr['id'] );
						$cntrlrId = $layout->addController( 'SC', array('mod' => $module) );
						$layout->save();
						$this->_event->success( t('Updated homepage module') );
					} else {
						$cntrlrId = $fpscCntrlr['id'];
					}
					return zula_redirect( $this->_router->makeUrl('content_layout', 'edit', 'fpsc-'.$siteType, null, array('id' => $cntrlrId)) );
				} else {
					$this->_event->error( t('Selected site type does not exist') );
				}
			} catch ( Input_KeyNoExist $e ) {
			}
			return zula_redirect( $this->_router->makeUrl('content_layout') );
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
				$layout = new Layout( $this->_input->post('content_layout_name') );
				// Update all of the controllers attributes
				$updated = 0;
				foreach( $this->_input->post( 'content_layout' ) as $cid=>$details ) {
					try {
						$cntrlr = $layout->getControllerDetails( $cid );
						$cntrlr['order'] = abs( $details['order'] );
						$cntrlr['sector'] = $details['sector'];
						$layout->editController( $cntrlr['id'], $cntrlr );
						$updated++;
					} catch ( Layout_ControllerNoExist $e ) {
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
				$layout = new Layout( $this->_input->post('content_layout_name') );
				$resources = array();
				$delCount = 0;
				foreach( $this->_input->post( 'controller_ids' ) as $cntrlrId ) {
					try {
						$layout->detachController( $cntrlrId );
						++$delCount;
						// Store resource IDs to delete
						$resources[] = 'layout_controller_'.$cntrlrId;
					} catch ( Layout_ControllerNoExist $e ) {
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
