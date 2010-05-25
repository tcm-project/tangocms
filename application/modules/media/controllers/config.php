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
										t('Manage Outstanding')	=> $this->_router->makeUrl( 'media', 'manage', 'outstanding' ),
										t('Settings')			=> $this->_router->makeUrl( 'media', 'config', 'settings' ),
										));
		}

		/**
		 * Displays all of the categories that the user has permission to.
		 *
		 * @return string
		 */
		public function indexSection() {
			$this->_i18n->textDomain( $this->textDomain() );
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
			$this->_i18n->textDomain( $this->textDomain() );
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
			$this->_i18n->textDomain( $this->textDomain() );
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
			$this->_i18n->textDomain( $this->textDomain() );
			$this->setTitle( t('Media Settings') );
			$this->setOutputType( self::_OT_CONFIG );
			if ( !$this->_acl->check( 'media_manage_settings' ) ) {
				throw new Module_NoPermission;
			}
			// Prepare the form of settings
			$mediaConf = $this->_config->get( 'media' );
			$form = new View_form( 'config/settings.html', 'media' );
			$form->addElement( 'media/per_page', $mediaConf['per_page'], t('Per page'), new Validator_Int )
				 ->addElement( 'media/use_lightbox', $mediaConf['use_lightbox'], t('Use lightbox'), new Validator_Bool )
				 ->addElement( 'media/max_fs', $mediaConf['max_fs'], t('Maximum file size'), new Validator_Int )
				 ->addElement( 'media/max_thumb_width', $mediaConf['max_thumb_width'], t('Thumbnail width'), new Validator_Between(20, 200) )
				 ->addElement( 'media/max_image_width', $mediaConf['max_image_width'], t('Maximum image width'), new Validator_Between(200, 90000) )
				 ->addElement( 'media/wm_position', $mediaConf['wm_position'], t('Watermark position'),
								new Validator_InArray( array('t', 'tr', 'r', 'br', 'b', 'bl', 'l', 'tl') ),
								false
							 );
			if ( $form->hasInput() && $form->isValid() ) {
				$purgeTmpImages = false;
				foreach( $form->getValues( 'media' ) as $key=>$val ) {
					if (
						($key == 'max_image_width' && $mediaConf['max_image_width'] != $val)
						||
						($key == 'wm_position' && $mediaConf['wm_position'] != $val)
					) {
						$purgeTmpImages = true;
					} else if ( $key == 'max_fs' ) {
						$val = zula_byte_value( $val.$this->_input->post('media/max_fs_unit') );
					}
					$this->_config_sql->update( 'media/'.$key, $val );
				}
				// Upload the watermark
				if ( $this->_input->has( 'post', 'media_wm_delete' ) ) {
					unlink( $this->_zula->getDir('uploads').'/media/wm.png' );
					unlink( $this->_zula->getDir('uploads').'/media/wm_thumb.png' );
					$purgeTmpImages = true;
				}
				try {
					$uploader = new Uploader( 'media_wm', $this->_zula->getDir('uploads').'/media' );
					$uploader->subDirectories( false )
							 ->allowImages();
					$file = $uploader->getFile();
					if ( $file->upload() !== false ) {
						$image = new Image( $file->path );
						$image->mime = 'image/png';
						$image->save( $file->dirname.'/wm.png', false );
						$image->thumbnail( 80, 80 )
							  ->save( $file->dirname.'/wm_thumb.png' );
						$purgeTmpImages = true;
					}
				} catch ( Uploader_NotEnabled $e ) {
					$this->_event->error( t('Sorry, it appears file uploads are disabled within your PHP configuration') );
				} catch ( Uploader_MaxFileSize $e ) {
					$msg = sprintf( t('Selected file exceeds the maximum allowed file size of %s'),
									zula_human_readable($e->getMessage())
								);
					$this->_event->error( $msg );
				} catch ( Uploader_InvalidMime $e ) {
					$this->_event->error( t('Sorry, the uploaded file is of the wrong file type') );
				} catch ( Uploader_Exception $e ) {
					$this->_log->message( $e->getMessage(), Log::L_WARNING );
					$this->_event->error( t('Oops, an error occurred while uploading your files') );
				} catch ( Image_Exception $e ) {
					$this->_log->message( $e->getMessage(), Log::L_WARNING );
					$this->_event->error( t('Oops, an error occurred while processing an image') );
				}
				// Purge tmp images if needed and redirect
				if ( $purgeTmpImages ) {
					$files = (array) glob( $this->_zula->getDir('tmp').'/media/max*-*' );
					foreach( array_filter( $files ) as $tmpFile ) {
						unlink( $tmpFile );
					}
				}
				$this->_event->success( t('Updated media settings') );
				return zula_redirect( $this->_router->makeUrl('media', 'config', 'settings') );
			}
			if ( is_file( $this->_zula->getDir('uploads').'/media/wm_thumb.png' ) ) {
				$wmThumbPath = $this->_zula->getDir( 'uploads', true ).'/media/wm_thumb.png';
			} else {
				$wmThumbPath = null;
			}
			$form->assign( array('WM_THUMB_PATH' => $wmThumbPath) );
			return $form->getOutput();
		}

	}

?>
