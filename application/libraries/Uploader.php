<?php

/**
 * Zula Framework Uploader
 * --- A simple upload handler class to assist with uploading of files
 * Any file uploaded will be stored using a random file name by default
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Uploader
 */

	class Uploader extends Zula_LibraryBase implements Iterator {

		/**
		 * Upload name/key to be used
		 * @var string
		 */
		protected $uploadName = null;

		/**
		 * Holds details for all of the files
		 * @var array
		 */
		protected $files = array();

		/**
		 * How many files there are for the upload name
		 * @var int
		 */
		protected $fileCount = 0;

		/**
		 * Main configuration array that will be used for
		 * all of the uploaded files.
		 * @var array
		 */
		protected $config = array(
								'allowed_mime'	=> array(),
								'upload_dir'	=> '',
								'max_file_size'	=> 0,
								'sub_dir'		=> true,
								'overwrite'		=> false,
								);

		/**
		 * Holds which index/iteration we are on for the files
		 * @var int
		 */
		private $filePosition = 0;

		/**
		 * Constructor
		 * Sets the file key/name to be used for uploading
		 *
		 * @param string $uploadName
		 * @param string $uploadDir
		 * @return object
		 */
		public function __construct( $uploadName, $uploadDir=null ) {
			if ( ini_get( 'file_uploads' ) == false ) {
				throw new Uploader_NotEnabled( 'file uploads are currently disabled with the PHP configuration' );
			} else if ( isset( $_FILES[ $uploadName ] ) ) {
				$this->uploadName = $uploadName;
				$this->fileCount = count( $_FILES[ $uploadName ]['tmp_name'] );
				if ( $this->fileCount > 1 ) {
					for( $i = 0; $i < $this->fileCount; $i++ ) {
						$this->files[] = array(
											'name'		=> str_replace( "\0", '', $_FILES[ $uploadName ]['name'][ $i ] ),
											'tmp_name'	=> $_FILES[ $uploadName ]['tmp_name'][ $i ],
											'size'		=> $_FILES[ $uploadName ]['size'][ $i ],
											'error'		=> $_FILES[ $uploadName ]['error'][ $i ],
											'mime'		=> null,
											);
					}
				} else {
					$this->files[] = $_FILES[ $uploadName ];
					$this->files[0]['name'] = str_replace( "\0", '', $this->files[0]['name'] );
					$this->files[0]['mime'] = null;
					unset( $this->files[0]['type'] ); # This is re-genereated with a more reliable method
				}
				$this->uploadDir( $uploadDir );
				// Set max file size to that of the current php.ini default
				$this->maxFileSize( ini_get( 'upload_max_filesize' ) );
			} else {
				throw new Uploader_NoExist( 'Upload file name "'.$uploadName.'" does not exist' );
			}
		}

		/**
		 * Sets the directory to where files will be uploaded
		 * to. If left blank, then it will revert to the
		 * default sets within Zula.
		 *
		 * @param string $dir
		 * @return bool|object
		 */
		public function uploadDir( $dir=null ) {
			if ( !trim( $dir ) ) {
				$dir = $this->_zula->getDir( 'uploads' );
			} else if ( preg_match( '#[^A-Z0-9_\-{}./]#i', $dir ) ) {
				trigger_error( 'Uploader::uploadDir() invalid directory name', E_USER_WARNING );
				return false;
			}
			$this->config['upload_dir'] = rtrim( $dir, '/\ ' );
			return $this;
		}

		/**
		 * Set maximum file size for uploaded files. Also
		 * accepts PHP short-hand byte value, ie '9m' or
		 * '5k', or '4g'
		 *
		 * @param int $fileSize
		 * @return bool|object
		 */
		public function maxFileSize( $fileSize ) {
			$this->config['max_file_size'] = zula_byte_value( $fileSize );
			return $this;
		}

		/**
		 * Sets the allowed mime types
		 *
		 * @param array|string $mime
		 * @return object
		 */
		public function allowedMime( $mime=null ) {
			$this->config['allowed_mime'] = array_map( 'strtolower', (array) $mime );
			return $this;
		}

		/**
		 * Quick method to easily add in allowed mime types for
		 * common images. Note, this *appends* to the allowed mimes
		 *
		 * @return object
		 */
		public function allowImages() {
			$this->config['allowed_mime'] = array_merge(
														$this->config['allowed_mime'],
														array('image/gif', 'image/jpeg', 'image/png')
													   );
			return $this;
		}

		/**
		 * Sets if sub-directories should be made
		 *
		 * @param bool $subdir
		 * @return object
		 */
		public function subDirectories( $subdir=true ) {
			$this->config['sub_dir'] = (bool) $subdir;
			return $this;
		}

		/**
		 * Sets if a file can be overwritten, when providing a custom
		 * name for the file when uploading (instead of random)
		 *
		 * @param bool $allow
		 * @return object
		 */
		public function allowOverwrite( $allow=true ) {
			$this->config['overwrite'] = (bool) $allow;
			return $this;
		}

		/**
		 * Returns instance of Uploader_File for the current file
		 * index/iterator, or false on failure.
		 *
		 * @param int $key
		 * @return object|false
		 */
		public function getFile( $key=null ) {
			if ( $key === null ) {
				$key = $this->filePosition;
			}
			if ( isset( $this->files[ $key ] ) ) {
				// Construct a new object to hold all the details
				return new Uploader_File( $this->files[ $key ], $this->config );
			} else {
				return false;
			}
		}

		/**
		 * PHP Iterator Methods
		 */
		public function rewind() {
			$this->filePosition = 0;
		}

		public function current() {
			return $this->getFile();
		}

		public function key() {
			return $this->filePosition;
		}

		public function next() {
			++$this->filePosition;
		}

		public function valid() {
			return isset( $this->files[ $this->filePosition ] );
		}

	}

?>
