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
									 'application/octet-stream',
									 'application/x-flash-video',
									 'audio/mpeg',
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
			$this->_locale->textDomain( $this->textDomain() );
			$type = substr( $name, 0, -7 );
			if ( $type != 'external' && $type != 'upload' ) {
				throw new Module_ControllerNoExist;
			}
			$this->setTitle( t('Add new media item') );
			// Get details for the category we'll be adding to
			try {
				$cid = $this->_router->getArgument( 'id' );
				$category = $this->_model()->getCategory( $cid );
				$resource = 'media-cat_upload_'.$category['id'];
				if ( !$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource ) ) {
					throw new Module_NoPermission;
				}
				// Prepare form validation
				$form = new View_form( 'add/'.$type.'.html', 'media' );
				$form->addElement( 'media/cid', $category['id'], 'CID', new Validator_Confirm($category['id']) );
				$form->addElement( 'media/title', null, t('Title'), new Validator_Length(1, 255), ($type == 'upload') );
				$form->addElement( 'media/desc', null, t('Description'), new Validator_Length(0, 1000) );
				if ( $type == 'external' ) {
					// Specific for external media/YouTube
					$form->addElement( 'media/external_id', null, t('External Media ID'), new Validator_Length(6, 128) );
					$form->addElement( 'media/external_service', null, t('External Service'), new Validator_Length(2, 32) );
				}
				if ( $form->hasInput() && $form->isValid() ) {
					// Handle the provided form data to correctly add the item
					try {
						if ( $type == 'upload' ) {
							$details = $this->handleUpload( $form->getValues('media') );
							$details['external'] = array('service' => '', 'id' => '');
						} else if ( $type == 'external' ) {
							$details = $this->handleExternal( $form->getValues('media') );
						}
						// Everything is now done, add the new media item
						$item = $this->_model()->addItem(
														$category['id'], $details['title'], $details['desc'],
														$details['type'], $details['file'], $details['thumbnail'],
														$details['external']['service'], $details['external']['id']
														);
						$this->_event->success( t('Added new media item') );
						return zula_redirect( $this->_router->makeUrl( 'media', 'view', $item['clean_name'] ) );
					} catch ( Media_Exception $e ) {
						$this->_event->error( $e->getMessage() );
					}
				}
				$form->assign( array('CATEGORY' => $category) );
				return $form->getOutput();
			} catch ( Router_ArgNoExist $e ) {
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
				$uploader->allowedMime( $this->allowedMime );
				$file = $uploader->getFile();
				if ( $file->upload() === false ) {
					throw new Media_Exception( t('Please select a file to upload') );
				} else if ( $file->category == 'image' ) {
					// Make the medium image sizes
					$image = new Image( $file->path );
					$image->resize(
									$this->_config->get('media/medium_size_x'),
									$this->_config->get('media/medium_size_y'),
									false
								  );
					$image->save( $file->dirname.'/medium_'.$file->basename );
				}
				/**
				 * Attempt to upload the thumbnail image. If one is not provided
				 * then use the image uploaded (if it is an image)
				 */
				try {
					$uploader = new Uploader( 'media_thumb', $file->dirname );
					$uploader->allowedMime( array('image/gif', 'image/jpeg', 'image/png') )
							 ->subDirectories(false);
					$thumbnail = $uploader->getFile();
					if ( $thumbnail->upload() !== false ) {
						$thumbnailPath = $thumbnail->path;
					} else if ( $file->category == 'image' ) {
						// Create thumbnail from the main image
						$thumbnailPath = $file->path;
					}
					if ( isset( $thumbnailPath ) ) {
						$thumbImage = new Image( $thumbnailPath );
						$thumbImage->mime = 'image/png';
						$thumbImage->thumbnail(
												$this->_config->get('media/thumb_size_x'),
												$this->_config->get('media/thumb_size_y')
											  );
						$thumbnailName = 'thumb_'.$file->filename.'.png';
						$thumbImage->save( $file->dirname.'/'.$thumbnailName );
					}
					// Remove the original uploaded file, if it exists
					if ( isset( $thumbnail->path ) ) {
						unlink( $thumbnail->path );
					}
				} catch ( Image_Exception $e ) {
					// We don't care so much if thumbnail can't be made, handle it differently.
					$this->_log->message( 'failed to create thumbnail: '.$e->getMessage(), Log::L_NOTICE );
				}
				return array(
							'title'		=> $fd['title'],
							'desc'		=> $fd['desc'],
							'type'		=> $file->category,
							'file'		=> $file->basename,
							'thumbnail'	=> isset($thumbnailName) ? $thumbnailName : '',
							);
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
			$chars = '1234567890ABCDEFGHIJKLMNOPQRSUTVWXYZabcdefghijklmnopqrstuvwxyz';
			$charsLen = strlen( $chars );
			$uploadDir = $this->_zula->getDir( 'uploads' ).'/media/'.$fd['cid'].'/external';
			do {
				$uid = '';
				for( $i=0; $i <= 9; $i++ ) {
					$uid .= substr( $chars, rand(0, $charsLen), 1 );
				}
				$uploadDir .= '/'.$uid;
			} while ( file_exists( $uploadDir ) || is_dir( $uploadDir ) );
			// Attempt to make the needed directory
			if ( zula_make_dir( $uploadDir ) ) {
				$thumbnailName = 'thumb_'.$uid.'.'.pathinfo( $externalMedia->thumbUrl, PATHINFO_EXTENSION );
				if ( copy( $externalMedia->thumbUrl, $uploadDir.'/'.$thumbnailName ) ) {
					// Resize the thumbnail image
					try {
						$thumbnail = new Image( $uploadDir.'/'.$thumbnailName );
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
					$thumbnailName = '';
				}
				return array(
							'title'		=> empty($fd['title']) ? $externalMedia->title : $fd['title'],
							'desc'		=> empty($fd['desc']) ? $externalMedia->description : $fd['desc'],
							'type'		=> 'external',
							'file'		=> '',
							'thumbnail'	=> $thumbnailName,
							'external'	=> array(
												'service'	=> $fd['external_service'],
												'id'		=> $fd['external_id'],
												),
							);
			} else {
				throw new Media_Exception( t('Unable to create directory') );
			}
		}

	}

?>
