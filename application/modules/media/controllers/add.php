<?php

/**
 * Zula Framework Module (media)
 * --- Adds a new media item
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Media
 */

	class Media_controller_add extends Zula_ControllerBase {

		/**
		 * Array of all allowed mime_types that can be uploaded
		 * @var array
		 */
		protected $allowedMime = array(
									 'image/gif',
									 'image/jpeg',
									 'image/png',
									 'video/x-flv',
									 'video/mp4',
									 'audio/mpeg',
									 'application/octet-stream',
									 'application/x-flash-video',
									 );

		/**
		 * Handles adding a new media item to a category, either uploaded or from
		 * external media such as a YouTube video.
		 *
		 * @param string $name
		 * @param array $args
		 * @return mixed
		 */
		public function __call( $name, $args ) {
			$this->_i18n->textDomain( $this->textDomain() );
			$type = substr( $name, 0, -7 );
			if ( $type != 'external' && $type != 'upload' ) {
				throw new Module_ControllerNoExist;
			}
			$this->setTitle( t('Add new media item') );
			// Get details for the category we'll be adding to
			try {
				$cid = $this->_input->get( 'cid' );
				$category = $this->_model()->getCategory( $cid );
				$resource = 'media-cat_upload_'.$category['id'];
				if ( !$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource ) ) {
					throw new Module_NoPermission;
				}
				// Prepare form validation
				$form = new View_form( 'add/'.$type.'.html', 'media' );
				$form->addElement( 'media/cid', $category['id'], 'CID', new Validator_Confirm($category['id']) );
				if ( $type == 'external' ) {
					// Specific for external media/YouTube
					$form->addElement( 'media/external_id', null, t('External Media ID'), new Validator_Length(6, 128) );
					$form->addElement( 'media/external_service', null, t('External Service'), new Validator_Length(2, 32) );
				}
				if ( $form->hasInput() && $form->isValid() ) {
					// Handle the provided form data to correctly add the item
					try {
						if ( $type == 'upload' ) {
							$uploadedFiles = $this->handleUpload( $form->getValues('media') );
						} else if ( $type == 'external' ) {
							$uploadedFiles = $this->handleExternal( $form->getValues('media') );
						}
						$fileCount = count( $uploadedFiles );
						foreach( $uploadedFiles as $file ) {
							if ( !isset( $file['external'] ) ) {
								$file['external'] = array('service' => '', 'id' => '');
							}
							$lastItem = $this->_model()->addItem( $category['id'], $file['title'], $file['desc'], $file['type'], $file['file'],
																  $file['thumbnail'], $file['external']['service'], $file['external']['id'] );
						}
						if ( $fileCount == 1 ) {
							$this->_event->success( t('Added new media item') );
						} else {
							$this->_event->success( sprintf('Added %1$d new media items', $fileCount) );
						}
						return zula_redirect( $this->_router->makeUrl('media', 'manage', 'outstanding')
															->queryArgs( array('cid' => $category['id']) )
											);
					} catch ( Media_Exception $e ) {
						$this->_event->error( $e->getMessage() );
					}
				}
				$form->assign( array(
									'CATEGORY'	=> $category,
									'MAX_FS'	=> $this->_config->get('media/max_fs'),
									));
				return $form->getOutput();
			} catch ( Input_KeyNoExist $e ) {
				$this->_event->error( t('No media category selected') );
			} catch ( Media_CategoryNoExist $e ) {
				$this->_event->error( t('Media category does not exist') );
			}
			return zula_redirect( $this->_router->makeUrl( 'media' ) );
		}

		/**
		 * Handles the uploading and image post-processing if needed.
		 *
		 * @param array $fd
		 * @return array
		 */
		protected function handleUpload( array $fd ) {
			try {
				$uploader = new Uploader( 'media_file', $this->_zula->getDir( 'uploads' ).'/media/'.$fd['cid'].'/{CATEGORY}' );
				$uploader->subDirectories()
						 ->allowedMime( $this->allowedMime )
						 ->maxFileSize( $this->_config->get('media/max_fs') )
						 ->extractArchives();
				$file = $uploader->getFile();
				if ( $file->upload() === false ) {
					throw new Media_Exception( t('Please select a file to upload') );
				}
				// Upload the thumbail image if one has been provided and resize it
				$thumbUploader = new Uploader( 'media_thumb', $file->dirname );
				$thumbUploader->subDirectories(false)
							  ->allowImages();
				$thumbnail = $thumbUploader->getFile();
				if ( $thumbnail->upload() !== false ) {
					$thumbImage = new Image( $thumbnail->path );
					$thumbImage->mime = 'image/png';
					$thumbImage->resize( $this->_config->get('media/max_thumb_width'), null, false );
					// Remove the original uploaded file
					unlink( $thumbnail->path );
				}
				/**
				 * Get details of all the images (could have been an archive containing
				 * multiple media files
				 */
				$uploadedItems = array();
				while( $details = $file->getDetails() ) {
					if ( isset( $details['path'] ) ) {
						// Get the directory name where the files are stored (just the name, not path)
						$dirname = substr( $details['dirname'], strrpos($details['dirname'], DIRECTORY_SEPARATOR)+1 );
						/**
						 * Use uploaded thumbnail, or attempt to create one from the uploaded image
						 */
						$thumbname = $details['filename'].'_thumb.png';
						if ( isset( $thumbImage ) ) {
							$thumbImage->save( $details['dirname'].'/'.$thumbname, false );
						} else if ( $details['category'] == 'image' ) {
							$tmpThumb = new Image( $details['path'] );
							$tmpThumb->mime = 'image/png';
							$tmpThumb->resize( $this->_config->get('media/max_thumb_width'), null, false )
									 ->save( $details['dirname'].'/'.$thumbname );
						} else {
							unset( $thumbname );
						}
						// Generate a title from the filename automatically
						$title = str_replace( array('-', '_', '+'), ' ', pathinfo($details['name'], PATHINFO_FILENAME) );
						$uploadedItems[] = array(
												'title'		=> trim( ucfirst(strtolower($title)) ),
												'desc'		=> '',
												'type'		=> $details['category'],
												'file'		=> $dirname.'/'.$details['basename'],
												'thumbnail'	=> isset($thumbname) ? $dirname.'/'.$thumbname : '',
												);
					}
				}
				if ( isset( $thumbImage ) ) {
					$thumbImage->destroy();
				}
				return $uploadedItems;
			} catch ( Uploader_NotEnabled $e ) {
				$msg = t('Sorry, it appears file uploads are disabled within your PHP configuration');
			} catch ( Uploader_MaxFileSize $e ) {
				$msg = sprintf( t('Selected file exceeds the maximum allowed file size of %s'),
								zula_human_readable($e->getMessage())
							   );
			} catch ( Uploader_InvalidMime $e ) {
				$msg = t('Sorry, the uploaded file is of the wrong file type');
			} catch ( Uploader_Exception $e ) {
				$logMsg = $e->getMessage();
				$msg = t('Oops, an error occurred while uploading your files');
			} catch ( Image_Exception $e ) {
				$logMsg = $e->getMessage();
				$msg = t('Oops, an error occurred while processing an image');
			}
			// Cleanup and end processing, it failed.
			if ( isset( $file->dirname ) ) {
				zula_full_rmdir( $file->dirname );
			}
			if ( isset( $logMsg ) ) {
				$this->_log->message( $logMsg, Log::L_WARNING );
			}
			throw new Media_Exception( $msg );
		}

		/**
		 * Handles adding a media item from an external source, such as YouTube.
		 *
		 * @param array $fd
		 * @return array
		 */
		protected function handleExternal( array $fd ) {
			try {
				$externalMedia = Externalmedia::factory( $fd['external_service'], $fd['external_id'] );
			} catch ( ExternalMediaDriver_InvalidID $e ) {
				throw new Media_Exception( t('External media ID does not exist') );
			} catch ( Externalmedia_Exception $e ) {
				throw new Media_Exception( $e->getMessage() );
			}
			// Generate a random directory as the uploader would, to store thumbnail
			$uploadDir = $this->_zula->getDir( 'uploads' ).'/media/'.$fd['cid'].'/external';
			if ( ($dirname = zula_make_unique_dir($uploadDir)) ) {
				$uploadDir .= '/'.$dirname;
				$thumbname = $dirname.'_thumb.png';
				if ( copy( $externalMedia->thumbUrl, $uploadDir.'/'.$thumbname ) ) {
					// Resize the thumbnail image
					try {
						$thumbnail = new Image( $uploadDir.'/'.$thumbname );
						$thumbnail->mime = 'image/png';
						$thumbnail->thumbnail(
											$this->_config->get('media/thumb_size_x'),
											$this->_config->get('media/thumb_size_y')
											);
						$thumbnail->save();
					} catch ( Image_Exception $e ) {
						$this->_event->error( t('Oops, an error occurred while processing an image') );
						$this->_log->message( $e->getMessage(), Log::L_WARNING );
					}
				} else {
					$this->_event->error( t('Unable to save thumbnail image') );
					$thumbname = null;
				}
				return array( array(
								'title'		=> empty($fd['title']) ? $externalMedia->title : $fd['title'],
								'desc'		=> empty($fd['desc']) ? $externalMedia->description : $fd['desc'],
								'type'		=> 'external',
								'file'		=> '',
								'thumbnail'	=> isset($thumbname) ? $dirname.'/'.$thumbname : '',
								'external'	=> array(
													'service'	=> $fd['external_service'],
													'id'		=> $fd['external_id'],
													),
								)
							);
			} else {
				throw new Media_Exception( t('Unable to create directory') );
			}
		}

	}

?>
