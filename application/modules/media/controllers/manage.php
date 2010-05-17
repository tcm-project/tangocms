<?php

/**
 * Zula Framework Module (media)
 * --- Manages a media item, ie - Edit/Delete
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Media
 */

	class Media_controller_manage extends Zula_ControllerBase {

		/**
		 * Edit a media item (only Name/Title and Description)
		 *
		 * @return string
		 */
		public function editSection() {
			$this->_i18n->textDomain( $this->textDomain() );
			$this->setTitle( t('Edit Media Item') );
			// Get details for the media item to edit
			try {
				$itemId = $this->_router->getArgument( 'id' );
				$item = $this->_model()->getItem( $itemId );
				// Get parent category permission
				$resource = 'media-cat_moderate_'.$item['cat_id'];
				if ( !$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource ) ) {
					throw new Module_NoPermission;
				}
				// Prepare form validation
				$form = new View_form( 'manage/edit.html', 'media', false );
				$form->addElement( 'media/id', $item['id'], 'id', new Validator_Int );
				$form->addElement( 'media/title', $item['name'], t('Title'), new Validator_Length(1, 255) );
				$form->addElement( 'media/desc', $item['description'], t('Description'), new Validator_Length(0, 1000) );
				if ( $form->hasInput() && $form->isValid() ) {
					$fd = $form->getValues( 'media' );
					$this->_model()->editItem( $item['id'], $fd['title'], $fd['desc'] );
					$this->_event->success( t('Edited media item') );
					return zula_redirect( $this->_router->makeUrl( 'media', 'view', $item['clean_name'] ) );
				}
				return $form->getOutput();
			} catch ( Router_ArgNoExist $e ) {
				$this->_event->error( t('No media item selected') );
			} catch ( Media_ItemNoExist $e ) {
				$this->_event->error( t('Media item does not exist') );
			}
			return zula_redirect( $this->_router->makeUrl( 'media' ) );
		}

		/**
		 * Deletes a media item from a category if it exists
		 *
		 * @return string
		 */
		public function deleteSection() {
			$this->_i18n->textDomain( $this->textDomain() );
			$this->setTitle( t('Delete Media Item') );
			// Attempt to remove the single media item
			try {
				$itemId = $this->_router->getArgument( 'id' );
				$item = $this->_model()->getItem( $itemId );
				// Check permission to parent category resource
				$resource = 'media-cat_moderate_'.$item['cat_id'];
				if ( $this->_acl->resourceExists( $resource ) && $this->_acl->check( $resource ) ) {
					if ( $this->_input->checkToken( 'get' ) ) {
						$this->_model()->deleteItem( $item['id'] );
						zula_full_rmdir( $item['path_fs'] );
						$this->_event->success( t('Deleted media item') );
						// Redirect back to the parent media category
						try {
							$category = $this->_model()->getCategory( $item['cat_id'] );
							return zula_redirect( $this->_router->makeUrl('media', 'cat', $category['clean_name']) );
						} catch ( Media_CatNoExist $e ) {
						}
					} else {
						$this->_event->error( Input::csrfMsg() );
					}
				} else {
					throw new Module_NoPermission;
				}
			} catch ( Router_ArgNoExist $e ) {
				$this->_event->error( t('No media item selected') );
			} catch ( Media_ItemNoExist $e ) {
				$this->_event->error( t('Media item does not exist') );
			}
			return zula_redirect( $this->_router->makeUrl( 'media' ) );
		}

	}

?>
