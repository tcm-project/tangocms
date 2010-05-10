<?php

/**
 * Zula Framework Uploader_File
 * --- Provides an OOP interface to get details about the uploaded
 * file, and methods to actually upload/move the file.
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, 2009, 2010 Alex Cartwright
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
		 * Uploader object, to get details from
		 * @var object
		 */
		protected $uploader = null;

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
		 * @param object $uploader
		 * @return object
		 */
		public function __construct( array $fileDetails, Uploader $uploader ) {
			$this->fileDetails[] = $fileDetails;
			$this->uploader = $uploader;
		}

		/**
		 * Quick access to details of the first file, such as file size
		 * mime type, path etc.
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
			return isset($this->fileDetails[0][$name]) ? $this->fileDetails[0][ $name ] : parent::__get( $name );
		}

		/**
		 * Returns details about uploaded files. Multiple details will
		 * only exist if an archive was uploaded and was able to extract.
		 *
		 * bool false will only be returned if multiple details and
		 * we have reached the end of all extracted files, or if the
		 * provided index does not exist.
		 *
		 * @param int $index
		 * @return array|bool
		 */
		public function getDetails( $index=null ) {
			static $pointer = 0;
			if ( $index === null ) {
				$index = $pointer++;
			}
			return isset($this->fileDetails[$index]) ? $this->fileDetails[ $index ] : false;
		}

		/**
		 * Main method for uploading/moving the file. Various exceptions are
		 * thrown in this method to indicate the errors that could occur
		 * (such as the main PHP error codes).
		 *
		 * bool false is returned if there was no file uploaded,
		 *
		 * @param string $filename
		 * @return bool
		 */
		public function upload( $filename=null ) {
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
			if ( !is_uploaded_file( $this->tmpName ) ) {
				$this->_log->message( 'file was not uploaded, possible malicious attack', Log::L_WARNING );
				throw new Uploader_Exception( 'requested file was not actually uploaded, possible malicious attack' );
			}
			/**
			 * Find out the mime type + category of the file, then run some checks
			 * to ensure filesize/mime type etc are correct.
			 */
			$this->fileDetails[0]['mime'] = zula_get_file_mime( $this->tmpName );
			$this->fileDetails[0]['category'] = substr( $this->mime, 0, strpos($this->mime, '/') );
			if ( $this->mime == false ) {
				throw new Uploader_Exception( 'unable to find mime type for uploaded file' );
			} else if ( $this->checkFileSize( $this->size ) === false ) {
				throw new Uploader_MaxFileSize( $this->uploader->maxFileSize );
			} else if ( $this->checkMime( $this->mime ) === false ) {
				throw new Uploader_InvalidMime( sprintf( $this->errorMsg['mime'], $this->name, $this->mime ) );
			} else if ( $this->checkExtension( $this->name ) === false ) {
				throw new Uploader_InvalidExtension( sprintf( $this->errorMsg['file_ext'], $this->name ) );
			}
			// All is ok, attempt to move/extract the uploaded file
			if ( $this->uploader->extractArchives && ($this->mime =='application/zip' || $this->mime == 'application/x-tar') ) {
				if ( $this->mime == 'application/zip' ) {
					$uploaded = $this->handleZip( $filename );
				} else if ( $this->mime == 'application/x-tar' ) {
					$uploaded = $this->handleTar( $filename );
				}
				unlink( $this->tmpName );
				if ( $uploaded !== true ) {
					throw new Uploader_Exception( 'failed to extract files from archive' );
				}
			} else if ( $this->handleNormal( $filename ) === false ) {
				throw new Uploader_Exception( 'failed to move file "'.$this->name.'"' );
			}
			return true;
		}

		/**
		 * Handles the uploading/moving of a file in the standard way (as in
		 * not from extracting files in an archive
		 *
		 * @param string $filename
		 * @return bool
		 */
		protected function handleNormal( $filename ) {
			if ( ($uploadDir = $this->makeDirectory($this->category)) === false ) {
				throw new Uploader_Exception( 'unable to create upload directory, or not writable' );
			}
			$extension = pathinfo( $this->name, PATHINFO_EXTENSION );
			if ( $filename ) {
				$i = null;
				do {
					$path = $uploadDir.'/'.$filename.$i;
					if ( $extension ) {
						$path .= '.'.$extension;
					}
					++$i;
				} while ( $this->uploader->overwrite === false && file_exists( $path ) );
			} else {
				// Generate a unique name for this file
				$path = $uploadDir.'/'.zula_make_unique_file( $uploadDir, $extension, false );
			}
			$oldUmask = umask( 022 );
			if ( move_uploaded_file( $this->tmpName, $path ) ) {
				$this->fileDetails[0]['path'] = $path;
				$this->fileDetails[0] = array_merge( $this->fileDetails[0], pathinfo($path) );
				$this->fileDetails[0]['fromArchive'] = false;
				umask( $oldUmask );
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Handles the extraction of files in a ZIP archive
		 *
		 * @param string $filename
		 * @return bool
		 */
		protected function handleZip( $filename ) {
			$za = new ZipArchive;
			$za->open( $this->tmpName );
			$i = 0;
			while( $file = $za->statIndex($i) ) {
				$this->fileDetails[ ($i+1) ] = array(
													'name'			=> $file['name'],
													'size'			=> $file['size'],
													'mime'			=> null,
													'category'		=> null,
												);
				if ( $this->checkFileSize( $file['size'] ) && $this->checkExtension( $file['name'] ) ) {
					// Extract file to get the mime type, will be removed if it is not valid
					$extractDir = $this->_zula->getDir( 'tmp' ).'/uploader';
					$za->extractTo( $extractDir, $file['name'] );
					$mime = zula_get_file_mime( $extractDir.'/'.$file['name'] );
					$this->fileDetails[ ($i+1) ]['mime'] = $mime;
					$this->fileDetails[ ($i+1) ]['category'] = substr( $mime, 0, strpos($mime, '/') );
					if ( $mime !== false && $this->checkMime( $mime ) ) {
						// Move the file to a uniquely named file
						$category = $this->fileDetails[ ($i+1) ]['category'];
						if ( ($uploadDir = $this->makeDirectory( $category )) === false ) {
							throw new Uploader_Exception( 'unable to create upload directory, or not writable' );
						}
						$this->uploader->subDirectoryName( null ); # Stop the same sub directory being used!
						$path = $uploadDir.'/'.zula_make_unique_file($uploadDir,
																	 pathinfo($file['name'], PATHINFO_EXTENSION),
																	 false);
						rename( $extractDir.'/'.$file['name'], $path );
						$this->fileDetails[ ($i+1) ]['path'] = $path;
						$this->fileDetails[ ($i+1) ] = array_merge( $this->fileDetails[($i+1)], pathinfo($path) );
					} else {
						unlink( $uploadDir.'/'.$file['name'] );
					}
				}
				$this->fileDetails[ ($i+1) ]['fromArchive'] = true;
				++$i;
			}
			return true;
		}

		/**
		 * Makes the correct directory for the files to be uploaded
		 * to, a category can be provided as a token to replace.
		 *
		 * @param string $category
		 * @return string|bool
		 */
		protected function makeDirectory( $category=null ) {
			$uploadDir = str_replace( '{CATEGORY}', $category, $this->uploader->uploadDir );
			if ( $this->uploader->subDir === true ) {
				if ( !$this->uploader->subDirName ) {
					$this->uploader->subDirectoryName( pathinfo(zula_make_unique_dir($uploadDir), PATHINFO_BASENAME) );
				}
				$uploadDir .= '/'.$this->uploader->subDirName;
			}
			return (zula_make_dir( $uploadDir ) && is_writable( $uploadDir )) ? $uploadDir : false;
		}

		/**
		 * Checks the file size of the file with that
		 * of the maximum allowed
		 *
		 * @param int $size
		 * @return bool
		 */
		protected function checkFileSize( $size ) {
			return $this->uploader->maxFileSize == 0 || abs($size) <= $this->uploader->maxFileSize;
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
			if ( empty( $mime ) || empty( $this->uploader->allowedMime ) ) {
				return true;
			} else {
				foreach( array_unique($this->uploader->allowedMime) as $allowed ) {
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
