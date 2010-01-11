<?php

/**
 * Zula Framework Module
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2010, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Editor
 */

	class Editor_controller_upload extends Zula_ControllerBase {

		/**
		 * Handles file uploads from CKEdtiro
		 *
		 * @return string
		 */
		public function indexSection() {
			$this->_locale->textDomain( $this->textDomain() );
			$this->_dispatcher->standalone();
			// Handle uploading of the file
			try {
				$uploader = new Uploader( 'upload' );
				$uploader->uploadDir( $this->_zula->getDir('uploads').'/editor/' )
						 ->subDirectories( false )
						 ->allowImages();
				$file = $uploader->getFile();
				if ( $file->upload() === false ) {
					$error = t('Please select a file to upload');
				} else {
					$error = '';
				}
			} catch ( Uploader_NotEnabled $e ) {
				$error = t('Sorry, it appears file uploads are disabled within your PHP configuration');
			} catch ( Uploader_MaxFileSize $e ) {
				$maxSize = zula_byte_value( ini_get('upload_max_filesize') );
				if ( $maxSize >= pow(1024, 3) ) {
					$suffix = 'GiB';
					$maxSize /= pow(1024, 3);
				} else if ( $maxSize >= pow(1024, 2) ) {
					$suffix = 'MiB';
					$maxSize /= pow(1024, 2);
				} else if ( $maxSize >= 1024 ) {
					$suffix = 'KiB';
					$maxSize /= 1024;
				} else {
					$suffix = 'bytes';
				}
				$error = sprintf( t('Selected file exceeds the maximum allowed file size of %s'), number_format($maxSize).$suffix );
			} catch ( Uploader_Exception $e ) {
				$error = t('Oops, an error occurred while uploading your files');
			}
			// Return the HTML response that CKEditor requires
			$cb = $this->_input->get( 'CKEditorFuncNum' );
			return '<html><body>
						<script type="text/javascript">
							window.parent.CKEDITOR.tools.callFunction('.$cb.', "'.$file->path.'", "'.$error.'");
						</script>
					</body></html>';
		}

	}

?>
