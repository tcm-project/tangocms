<?php

/**
 * Zula Framework Module (media)
 * --- Configure media module, such as adding categories
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Media
 */

	class Media_controller_config extends Zula_ControllerBase {

		/**
		 * Constructor
		 * Sets common page links for configuration
		 *
		 * @return object
		 */
		public function __construct( $details, $config, $sector ) {
			parent::__construct( $details, $config, $sector );
			$this->setPageLinks( array(
										t('Manage Categories')	=> $this->_router->makeUrl( 'media', 'config' ),
										t('Add Category')		=> $this->_router->makeUrl( 'media', 'config', 'addcat'),
										t('Settings')			=> $this->_router->makeUrl( 'media', 'config', 'settings' ),
										));
		}

		/**
		 * Displays all of the categories that the user has permission to.
		 *
		 * @return string
		 */
		public function indexSection() {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('Manage Media') );
			// Check user has correct permission
			if ( !$this->_acl->checkMulti( array('media_add_category', 'media_edit_category', 'media_delete_category') ) ) {
				throw new Module_NoPermission;
			}
			$view = $this->loadView( 'config/main.html' );
			$view->assign( array(
								'CATEGORIES' => $this->_model( 'media' )->getAllCategories(),
								));
			$view->assignHtml( array(
									'CSRF' => $this->_input->createToken( true ),
									));
			return $view->getOutput();
		}

		/**
		 * Displays and handles adding of a new category.
		 *
		 * @return string
		 */
		public function addCatSection() {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('Add Media Category') );
			// Check permission
			if ( !$this->_acl->check( 'media_add_category' ) ) {
				throw new Module_NoPermission;
			}
			$form = $this->buildCatForm();
			if ( $form->hasInput() && $form->isValid() ) {
				$fd = $form->getValues( 'media' );
				$cid = $this->_model()->addCategory( $fd['name'], $fd['desc'] );
				// Update/Add all needed ACL resources
				foreach( array('media-cat_view_', 'media-cat_upload_', 'media-cat_moderate_') as $resource ) {
					try {
						$roles = $this->_input->post( 'acl_resources/'.$resource );
					} catch ( Input_KeyNoExist $e ) {
						$roles = array();
					}
					$this->_acl->allowOnly( $resource.$cid, $roles );
				}
				$this->_event->success( t('Added new media category') );
				return zula_redirect( $this->_router->makeUrl( 'media', 'config' ) );
			}
			return $form->getOutput();
		}

		/**
		 * Displays and handles editing of an existing category
		 *
		 * @return string
		 */
		public function editCatSection() {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('Edit Category') );
			if ( !$this->_acl->check( 'media_edit_category' ) ) {
				throw new Module_NoPermission;
			}
			// Get details for the category we are editing
			try {
				$cid = $this->_router->getArgument( 'id' );
				$category = $this->_model()->getCategory( $cid );
				$resource = 'media-cat_view_'.$category['id'];
				if ( !$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource ) ) {
					throw new Module_NoPermission;
				}
				$form = $this->buildCatForm( $category['id'], $category['name'], $category['description'] );
				if ( $form->hasInput() && $form->isValid() ) {
					$fd = $form->getValues( 'media' );
					$this->_model()->editCategory( $category['id'], $fd['name'], $fd['desc'] );
					// Update/Add needed ACL resources
					foreach( array('media-cat_view_', 'media-cat_upload_', 'media-cat_moderate_') as $resource ) {
						try {
							$resource .= $category['id'];
							$roles = $this->_input->post( 'acl_resources/'.$resource );
						} catch ( Input_KeyNoExist $e ) {
							$roles = array();
						}
						$this->_acl->allowOnly( $resource, $roles );
					}
					$this->_event->success( t('Edited media category') );
				} else {
					return $form->getOutput();
				}
			} catch ( Router_ArgNoExist $e ) {
				$this->_event->error( t('No media category selected') );
			} catch ( Media_CategoryNoExist $e ) {
				$this->_event->error( t('Media category does not exist') );
			}
			return zula_redirect( $this->_router->makeUrl( 'media', 'config' ) );
		}

		/**
		 * Builds form for adding or editing a media category
		 *
		 * @param int $cid
		 * @param string $name
		 * @param string $desc
		 * @return object
		 */
		protected function buildCatForm( $cid=null, $name=null, $desc=null ) {
			$form = new View_Form( 'config/form_cat.html', 'media', is_null($cid) );
			$form->addElement( 'media/name', $name, t('Name'), new Validator_Length(1, 255) );
			$form->addElement( 'media/desc', $desc, t('Description'), new Validator_Length(0, 255) );
			// Add additional data on
			$form->assign( array('OP' => is_null($cid) ? 'add' : 'edit') );
			$aclForm = $this->_acl->buildForm( array(
													t('View Media Category')	 => 'media-cat_view_'.$cid,
													t('Upload media items')		 => array('media-cat_upload_'.$cid, 'group_admin'),
													t('Edit/Delete media items') => array('media-cat_moderate_'.$cid, 'group_admin'),
											  ));
			$form->assignHtml( array('ACL_FORM' => $aclForm) );
			return $form;
		}

		/**
		 * Bridges between deleting, or purging a category.
		 *
		 * @return bool
		 */
		public function bridgeSection() {			
			$type = $this->_input->has( 'post', 'media_purge' ) ? 'purge' : 'delete';
			if ( !$this->_acl->resourceExists( 'media_'.$type.'_category' ) || !$this->_acl->check( 'media_'.$type.'_category' ) ) {
				throw new Module_NoPermission;
			} else if ( $this->_input->checkToken() ) {
				// Attempt to purge or delete
				try {
					$delCount = 0;
					$mediaDir = $this->_zula->getDir( 'uploads' ).'/media';
					foreach( $this->_input->post( 'media_cat_ids' ) as $cid ) {
						$resource = 'media-cat_moderate_'.$cid;
						if ( $this->_acl->resourceExists( $resource ) && $this->_acl->check( $resource ) ) {
							try {
								$method = $type == 'delete' ? 'deleteCategory' : 'purgeCategory';
								$this->_model()->$method( $cid );
								// Remove all media items
								zula_full_rmdir( $mediaDir.'/'.$cid );
								++$delCount;
							} catch ( Media_CategoryNoExist $e ) {
							}
						}
					}
					$this->_event->success( $type == 'delete' ? t('Deleted selected categories') : t('Purged selected categories') );
				} catch ( Input_KeyNoExist $e ) {
					$this->_event->error( t('No media categories selected') );
				}
			} else {
				$this->_event->error( Input::csrfMsg() );
			}
			return zula_redirect( $this->_router->makeUrl( 'media', 'config' ) );
		}

		/**
		 * Updates settings for the media module
		 *
		 * @return string
		 */
		public function settingsSection() {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('Media Settings') );
			$this->setOutputType( self::_OT_CONFIG );
			if ( !$this->_acl->check( 'media_manage_settings' ) ) {
				throw new Module_NoPermission;
			}
			if ( $this->_input->has( 'post', 'setting/media' ) ) {
				if ( $this->_input->checkToken() ) {
					// Update the settings
					foreach( $this->_input->post( 'setting/media' ) as $key=>$val ) {
						try {
							$this->_config_sql->update( 'media/'.$key, $val );
						} catch ( Config_KeyNoExist $e ) {
							$this->_event->error( $e->getMessage() );
						}
					}
					$this->_event->success( t('Updated Media Settings') );
				} else {
					$this->_event->error( Input::csrfMsg() );
				}
				return zula_redirect( $this->_router->makeUrl( 'media', 'config', 'settings' ) );
			} else {
				/**
				 * Display all of the needed media settings, fun.
				 */
				$html = new Html( 'setting[media][%s]' );
				$options = array(
								'yn' => array( t('Yes') => true, t('No') => false ),
								);
				$view = $this->loadView( 'config/settings.html' );
				$view->assignHtml( array(
									'S_THUMB_WIDTH'		=> $html->input( 'thumb_size_x', $this->_config->get( 'media/thumb_size_x' ) ),
									'S_THUMB_HEIGHT'	=> $html->input( 'thumb_size_y', $this->_config->get( 'media/thumb_size_y' ) ),
									'S_MEDIUM_WIDTH'	=> $html->input( 'medium_size_x', $this->_config->get( 'media/medium_size_x' ) ),
									'S_MEDIUM_HEIGHT'	=> $html->input( 'medium_size_y', $this->_config->get( 'media/medium_size_y' ) ),

									'S_PER_PAGE'		=> $html->input( 'per_page', $this->_config->get( 'media/per_page' ) ),
									'S_LIGHTBOX'		=> $html->radio( 'use_lightbox', $this->_config->get( 'media/use_lightbox' ), $options['yn'] ),

									'CSRF'				=> $this->_input->createToken( true ),
									));
				return $view->getOutput();
			}
		}

	}

?>
