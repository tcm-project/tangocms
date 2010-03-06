<?php

/**
 * Zula Framework Uploader_File
 * --- Provides an OOP interface to get details about the uploaded
 * file, and methods to actually upload/move the file.
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Uploader
 */

	class Uploader_File extends Zula_LibraryBase {

		/**
		 * Details about the uploaded file (very similar to
		 * that of the $_FILES array
		 * @var array
		 */
		protected $fileDetails = array();

		/**
		 * Uploader configuration
		 */
		protected $uploadConfig = array();

		/**
		 * Stores common errors that are used
		 * @var array
		 */
		protected $errorMsg = array(
									'partial'		=> 'requested file "%s" was only partially uploaded',
									'no_tmp_dir'	=> 'no temp directory for file "%s" to be uploaded to',
									'cant_write'	=> 'failed to write file "%s" to disk',
									'extension'		=> 'PHP extension blocked file upload for "%s"',
									'mime'			=> 'requested file "%s" has invalid mime of "%s"',
									'file_ext'		=> 'requested file "%s" has an invalid file extension',
									);

		/**
		 * Constructor
		 * Takes the file details and main Uploader config
		 *
		 * @param array $fileDetails
		 * @param array $uploadConfig
		 * @return object
		 */
		public function __construct( array $fileDetails, array $uploadConfig ) {
			$this->fileDetails = $fileDetails;
			$this->uploadConfig = $uploadConfig;
		}

		/**
		 * Get details about the file, such as File Size
		 * mime type, error code etc etc
		 *
		 * @param string $name
		 * @return mixed
		 */
		public function __get( $name ) {
			if ( $name == 'type' || $name == 'mime_type' ) {
				$name = 'mime';
			} else if ( $name == 'tmpName' ) {
				$name = 'tmp_name';
			}
			return isset($this->fileDetails[ $name ]) ? $this->fileDetails[ $name ] : parent::__get( $name );
		}

		/**
		 * Returns all of the details known about the
		 * uploaded file
		 *
		 * @return array
		 */
		public function getDetails() {
			ksort( $this->fileDetails );
			return $this->fileDetails;
		}

		/**
		 * Main method for uploading/moving the file. Various
		 * exceptions are thrown in this method to indicate
		 * the errors that could occur (such as the main PHP
		 * error codes).
		 *
		 * False is returned if there was no file uploaded, or
		 * something unexpected happened that was not handled
		 * by throwing an exception.
		 *
		 * @param string $fileName
		 * @return bool
		 */
		public function upload( $fileName=null ) {
			switch( $this->error ) {
				case UPLOAD_ERR_INI_SIZE:
					throw new Uploader_MaxFileSize( zula_byte_value( ini_get('upload_max_filesize') ) );

				case UPLOAD_ERR_FORM_SIZE:
					throw new Uploader_MaxFileSize( abs( $this->_input->post('MAX_FILE_SIZE') ) );

				case UPLOAD_ERR_PARTIAL:
					throw new Uploader_PartialUpload( sprintf( $this->errorMsg['partial'], $this->name ) );

				case UPLOAD_ERR_NO_FILE:
					return false;

				case UPLOAD_ERR_NO_TMP_DIR:
					throw new Uploader_NoTmpDir( sprintf( $this->errorMsg['no_tmp_dir'], $this->name ) );

				case UPLOAD_ERR_CANT_WRITE:
					throw new Uploader_NoWrite( sprintf( $this->errorMsg['cant_write'], $this->name ) );

				case UPLOAD_ERR_EXTENSION:
					throw new Uploader_FileBlocked( sprintf( $this->errorMsg['extension'], $this->name ) );
			}
			/**
			 * Find out the mime type of the file and then get the category
			 * of the file. The category is the first part of the mime type.
			 */
			$this->fileDetails['mime'] = zula_get_file_mime( $this->tmpName );
			if ( $this->fileDetails['mime'] == false ) {
				throw new Uploader_Exception( 'unable to find mime type for uploaded file' );
			}
			$mimeSplit = explode( '/', $this->mime, 2 );
			$this->fileDetails['category'] = $mimeSplit[0];
			/**
			 * Run some checks such as file-size, mime type etc and make sure
			 * they all pass, if not - stop the upload
			 */
			if ( $this->checkFileSize( $this->size ) === false ) {
				throw new Uploader_MaxFileSize( $this->uploadConfig['max_file_size'] );
			} else if ( $this->checkMime( $this->mime ) === false ) {
				throw new Uploader_InvalidMime( sprintf( $this->errorMsg['mime'], $this->name, $this->mime ) );
			} else if ( $this->checkExtension( $this->name ) === false ) {
				throw new Uploader_InvalidExtension( sprintf( $this->errorMsg['file_ext'], $this->name ) );
			}
			// All is ok, upload/move the file.
			if ( is_uploaded_file( $this->tmpName ) ) {
				$path = $this->createPath( $fileName );
				if ( $path ) {
					$oldUmask = umask( 022 );
					if ( move_uploaded_file( $this->tmpName, $path ) ) {
						umask( $oldUmask );
						return true;
					} else {
						throw new Uploader_Exception( 'failed to move file "'.$this->name.'" to "'.$path.'"' );
					}
				} else {
					// No path was created
					throw new Uploader_Exception( 'failed to create upload directory' );
				}
			} else {
				// The file was not actually uploaded
				$this->_log->message( 'attempted to move file, found file was not actually uploaded, possible malicious attack. tmp_name: "'.$this->tmpName.'"', Log::L_WARNING );
				throw new Uploader_Exception( 'requested file was not actually uploaded, possible malicious attack' );
			}
		}

		/**
		 * Parses the current upload directory to replace the
		 * following tokens:
		 *
		 * --- {CATEGORY}	Category of the file, eg; image
		 *
		 * The directory will also be created if it does not
		 * exist yet.
		 *
		 * @param string $fileName
		 * @return string|bool
		 */
		protected function createPath( $fileName=null ) {
			$replacements = array('{CATEGORY}' => $this->category);
			$dir = str_replace( array_keys($replacements),
								array_values($replacements),
								$this->uploadConfig['upload_dir']
							  );
			$fileExtension = pathinfo( $this->name, PATHINFO_EXTENSION );
			if ( $fileName === null ) {
				/**
				 * Generate a random path/file key that will be used for the
				 * file name, and a directory name if using sub-directories.
				 */
				$chars = '1234567890ABCDEFGHIJKLMNOPQRSUTVWXYZabcdefghijklmnopqrstuvwxyz';
				$charsLen = strlen( $chars );
				do {
					$uid = '';
					for( $i=0; $i <= 9; $i++ ) {
						$uid .= substr( $chars, rand(0, $charsLen), 1 );
					}
					$path = $dir.'/'.$uid;
					if ( $this->uploadConfig['sub_dir'] === false ) {
						$path .= '.'.$fileExtension;
					}
				} while ( file_exists( $path ) || is_dir( $path ) );
				if ( $this->uploadConfig['sub_dir'] === true ) {
					$path .= '/'.$uid.'.'.$fileExtension;
				}
			} else {
				$i = null;
				do {
					// See if we need to make a unique name for this file
					$path = $dir.'/'.$fileName.$i.'.'.$fileExtension;
					++$i;
				} while ( $this->uploadConfig['overwrite'] === false && file_exists( $path ) );
			}
			// Attempt to create the needed directory
			if ( zula_make_dir( dirname($path) ) ) {
				$this->fileDetails['uid'] = isset($uid) ? $uid : null;
				$this->fileDetails['path'] = $path;
				$this->fileDetails = array_merge( $this->fileDetails, pathinfo( $path ) );
				return $path;
			} else {
				return false;
			}
		}

		/**
		 * Checks the file size of the file with that
		 * of the maximum allowed
		 *
		 * @param int $size
		 * @return bool
		 */
		protected function checkFileSize( $size ) {
			return $this->uploadConfig['max_file_size'] == 0 || abs($size) <= $this->uploadConfig['max_file_size'];
		}

		/**
		 * Checks if the files mime type is of an allowed mime
		 * type listed. If no allowed mimes are set, then it
		 * will just return true.
		 *
		 * @param string $mime
		 * @return bool
		 */
		protected function checkMime( $mime ) {
			$mime = trim( $mime );
			if ( empty( $mime ) || empty( $this->uploadConfig['allowed_mime'] ) ) {
				return true;
			} else {
				foreach( array_unique($this->uploadConfig['allowed_mime']) as $allowed ) {
					if ( $allowed == $mime ) {
						return true;
					}
				}
				return false;
			}
		}

		/**
		 * Checks the extension of a file to make sure the uplaoded
		 * file is not a PHP, Perl etc script.
		 *
		 * @param string $name
		 * @return bool
		 */
		protected function checkExtension( $name ) {
			return !(bool) preg_match('#(?:php[0-9]?|pl|rb|aspx?|x?html?|exe|cgi)$#i', pathinfo($name, PATHINFO_EXTENSION));
		}

	}

?>
