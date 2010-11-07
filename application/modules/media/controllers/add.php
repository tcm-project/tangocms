<?php

/**
 * Zula Framework Module
 * Adds a new media item
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
									 'application/x-flash-video',
									 );

		/**
		 * Displays the form to either upload a file, or add an external
		 * media item (such as YouTube)
		 *
		 * @return string
		 */
		public function indexSection() {
			$this->setTitle( t('Add/upload media item') );
			// Get details for the category we'll be adding to
			try {
				$cid = $this->_input->get( 'cid' );
				$category = $this->_model()->getCategory( $cid );
				if ( !$this->_acl->check( 'media-cat_upload_'.$category['id'] ) ) {
					throw new Module_NoPermission;
				}
				$view = $this->loadView( 'add/form.html' );
				$view->assignHtml( array('csrf' => $this->_input->createToken(true)) );
				$view->assign( array(
								'cid'			=> $cid,
								'max_fs'		=> $this->_config->get('media/max_fs'),
								'zip_supported'	=> zula_supports( 'zipExtraction' ),
								));
				return $view->getOutput();
			} catch ( Input_KeyNoExist $e ) {
				$this->_event->error( t('No media category selected') );
			} catch ( Media_CategoryNoExist $e ) {
				$this->_event->error( t('Media category does not exist') );
			}
			return zula_redirect( $this->_router->makeUrl('media') );
		}

		/**
		 * Handles adding a new media item to a category, either uploaded or from
		 * external media such as a YouTube video.
		 *
		 * @param string $name
		 * @param array $args
		 * @return mixed
		 */
		public function __call( $name, $args ) {
			$type = substr( $name, 0, -7 );
			if ( $type != 'external' && $type != 'upload' ) {
				throw new Module_ControllerNoExist;
			}
			$this->setTitle( t('Add/upload new media item') );
			// Prepare the form validation
			$form = new View_form( 'add/form.html', 'media' );
			$form->addElement( 'media/cid', null, 'CID', new Validator_Int );
			if ( $type == 'external' ) {
				// Specific for external YouTube
				$form->addElement( 'media/external/service', null, t('External service'), new Validator_Length(1, 32) );
				$form->addElement( 'media/external/youtube_url', null, t('YouTube URL'), new Validator_Url );
			}
			if ( $form->hasInput() && $form->isValid() ) {
				/**
				 * Ensure the category exists and user has permission
				 */
				try {
					$category = $this->_model()->getCategory( $form->getValues('media/cid') );
					if ( !$this->_acl->check( 'media-cat_upload_'.$category['id'] ) ) {
						throw new Module_NoPermission;
					}
				} catch ( Media_CategoryNoExist $e ) {
					$this->_event->error( t('Media category does not exist') );
					return zula_redirect( $this->_router->makeUrl('media') );
				}
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
			// Redirect back to correct location
			$url = $this->_router->makeUrl( 'media', 'add' );
			try {
				$url->queryArgs( array('cid' => $this->_input->post('media/cid')) );
			} catch ( Input_KeyNoExist $e ) {
			}
			return zula_redirect( $url );
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
				$thumbnailWH = $this->_config->get( 'media/thumb_dimension' );
				$thumbUploader = new Uploader( 'media_thumb', $file->dirname );
				$thumbUploader->subDirectories(false)
							  ->allowImages();
				$thumbnail = $thumbUploader->getFile();
				if ( $thumbnail->upload() !== false ) {
					$thumbImage = new Image( $thumbnail->path );
					$thumbImage->mime = 'image/png';
					$thumbImage->thumbnail( $thumbnailWH, $thumbnailWH );
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
							$tmpThumb->thumbnail( $thumbnailWH, $thumbnailWH )
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
				parse_str( parse_url($fd['external']['youtube_url'], PHP_URL_QUERY), $queryArgs );
				if ( empty( $queryArgs['v'] ) ) {
					throw new Media_Exception( t('Please provide a valid YouTube URL') );
				}
				$serviceId = $queryArgs['v'];
				$externalMedia = Externalmedia::factory( $fd['external']['service'], $serviceId );
			} catch ( ExternalMediaDriver_InvalidID $e ) {
				throw new Media_Exception( t('Please provide a valid YouTube URL') );
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
						$thumbnailWH = $this->_config->get( 'media/thumb_dimension' );
						$thumbnail = new Image( $uploadDir.'/'.$thumbname );
						$thumbnail->mime = 'image/png';
						$thumbnail->thumbnail( $thumbnailWH, $thumbnailWH );
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
													'service'	=> $fd['external']['service'],
													'id'		=> $serviceId,
													),
								)
							);
			} else {
				throw new Media_Exception( t('Unable to create directory') );
			}
		}

	}

?>
