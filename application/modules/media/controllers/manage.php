<?php

/**
 * Zula Framework Module
 * Manages a media item, e.g. edit & delete
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Media
 */

	class Media_controller_manage extends Zula_ControllerBase {

		/**
		 * Lists all media items that are outstanding, for the user to add
		 * meta data such as title/description
		 *
		 * @return string
		 */
		public function outstandingSection() {
			$this->setTitle( t('Manage uploaded files') );
			try {
				$category = $this->_model()->getCategory( $this->_input->get('cid') );
				$resource = 'media-cat_upload_'.$category['id'];
				if ( !$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource ) ) {
					throw new Module_NoPermission;
				}
				$cid = (int) $category['id'];
			} catch ( Input_KeyNoExist $e ) {
				$cid = null;
			} catch ( Media_CategoryNoExist $e ) {
				$this->_event->error( t('Media category does not exist') );
				$cid = null;
			}
			/**
			 * Work out which URL to redirect back to, since if we've come from
			 * the 'config' cntrlr, we want to redirect back to that always.
			 */
			if ( empty($_SESSION['previous_url']) || !empty($_SESSION['media']['fromConfigCntrlr']) ) {
				$redirectUrl = $this->_router->makeUrl( 'media', 'config' );
			} else {
				$parsedPreviousUrl = new Router_Url( $_SESSION['previous_url'] );
				if ( $parsedPreviousUrl->module == 'media' && $parsedPreviousUrl->controller == 'config' ) {
					$_SESSION['media']['fromConfigCntrlr'] = true;
					$redirectUrl = $this->_router->makeUrl( 'media', 'config' );
				} else {
					$redirectUrl = new Router_Url( 'media' );
					if ( isset( $category['identifier'] ) ) {
						$redirectUrl->controller( 'cat' )
									->section( $category['identifier'] );
					}
				}
			}
			// Get all outstanding media items
			$outstanding = $this->_model()->getOutstandingItems( $cid );
			if ( ($count = count($outstanding)) == 0 ) {
				$this->_event->error( t('There are currently no outstanding media items') );
				return zula_redirect( $redirectUrl );
			} else {
				$form = new View_form( 'manage/outstanding.html', 'media' );
				$form->addElement( 'media/cid', $cid, 'cid', new Validator_Confirm($cid) );
				$form->addElement( 'media/item', null, t('Items'), array(new Validator_Is('array'), new Validator_Length($count, $count)) );
				if ( $form->hasInput() && $form->isValid() ) {
					/**
					 * Update the title and description for all provided media items
					 * however the ids must match the selecting outstanding items.
					 */
					$validItemIds = array_keys( $outstanding );
					// Validate the provided values
					$validatorName = new Validator_Length( 1, 255 );
					$validatorDesc = new Validator_Length( 0, 1000 );
					$successCount = 0;
					foreach( $form->getValues('media/item') as $key=>$item ) {
						if ( in_array( $key, $validItemIds ) ) {
							$valid = true;
							if ( ($errorMsg = $validatorName->validate($item['name'])) !== true ) {
								$valid = false;
								$this->_event->error( sprintf( $errorMsg, t('Titles') ) );
							}
							if ( ($errorMsg = $validatorDesc->validate($item['desc'])) !== true ) {
								$valid = false;
								$this->_event->error( sprintf( $errorMsg, t('Descriptions') ) );
							}
							if ( $valid ) {
								$this->_model()->editItem( $key, $item['name'], $item['desc'] );
								unset( $outstanding[ $key ] );
								++$successCount;
							}
						}
					}
					// Redirect back to the correct location
					if ( $successCount > 0 ) {
						$langStr = nt('Completed upload for 1 media item', 'Completed upload for %d media items', $successCount);
						$this->_event->success( sprintf($langStr, $successCount) );
					}
					if ( $successCount == $count ) {
						unset( $_SESSION['media']['fromConfigCntrlr'] );
						return zula_redirect( $redirectUrl );
					}
				}
				$form->assign( array(
									'CID'			=> $cid,
									'OUTSTANDING' 	=> $outstanding,
									));
				return $form->getOutput();
			}
		}

		/**
		 * Edit a media item (only Name/Title and Description)
		 *
		 * @return string
		 */
		public function editSection() {
			$this->setTitle( t('Edit media ttem') );
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
					return zula_redirect( $this->_router->makeUrl( 'media', 'view', $item['identifier'] ) );
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
			$this->setTitle( t('Delete media item') );
			// Attempt to remove the single media item
			try {
				$itemId = $this->_router->getArgument( 'id' );
				$item = $this->_model()->getItem( $itemId );
				// Check permission to parent category resource
				$resource = 'media-cat_moderate_'.$item['cat_id'];
				if ( $this->_acl->resourceExists( $resource ) && $this->_acl->check( $resource ) ) {
					if ( $this->_input->checkToken( 'get' ) ) {
						$this->_model()->deleteItem( $item['id'] );
						zula_full_rmdir( $item['path_fs'].'/'.dirname($item['filename']) );
						$this->_event->success( t('Deleted media item') );
						// Redirect back to the parent media category
						try {
							$category = $this->_model()->getCategory( $item['cat_id'] );
							return zula_redirect( $this->_router->makeUrl('media', 'cat', $category['identifier']) );
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
