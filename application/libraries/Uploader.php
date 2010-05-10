<?php

/**
 * Zula Framework Uploader
 * --- A simple upload handler class to assist with uploading of files
 * Any file uploaded will be stored using a random file name by default
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
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
		 * Main configuration array that will be used for
		 * all of the uploaded files.
		 * @var array
		 */
		protected $config = array(
								'allowedMime'		=> array(),
								'uploadDir'			=> '',
								'maxFileSize'		=> 0,
								'subDir'			=> true,
								'subDirName'		=> null,
								'overwrite'			=> false,
								'extractArchives'	=> false,
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
				if ( is_array( $_FILES[ $uploadName ]['tmp_name'] ) ) {
					$fileCount = count( $_FILES[ $uploadName ]['tmp_name'] );
					for( $i = 0; $i < $fileCount; $i++ ) {
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
		 * Get details about the uploader configuration
		 *
		 * @param string $name
		 * @return mixed
		 */
		public function __get( $name ) {
			return isset($this->config[ $name ]) ? $this->config[ $name ] : parent::__get( $name );
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
			}
			$this->config['uploadDir'] = rtrim( $dir, '/\ ' );
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
			$this->config['maxFileSize'] = zula_byte_value( $fileSize );
			return $this;
		}

		/**
		 * Sets the allowed mime types
		 *
		 * @param array|string $mime
		 * @return object
		 */
		public function allowedMime( $mime=null ) {
			$this->config['allowedMime'] = array_map( 'strtolower', (array) $mime );
			return $this;
		}

		/**
		 * Quick method to easily add in allowed mime types for
		 * common images. Note, this *appends* to the allowed mimes
		 *
		 * @return object
		 */
		public function allowImages() {
			$this->config['allowedMime'] = array_merge(
														$this->config['allowedMime'],
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
			$this->config['subDir'] = (bool) $subdir;
			return $this;
		}

		/**
		 * Sets the name of the sub directory to use, if not provided
		 * then a random one will be generated for you and used for all
		 * multiple file uploads.
		 *
		 * @param string $name
		 * @return object
		 */
		public function subDirectoryName( $name ) {
			$this->config['subDirName'] = (string) $name;
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
		 * Allow archives (.tar or .zip) to be uploaded and the contents
		 * extracted. Each file in the archive must match the allowed mime
		 * types and file size, if they don't they will not be kept.
		 *
		 * bool false will be returned if zipExtraction and tarExtraction
		 * are not supported.
		 *
		 * @param bool $extract
		 * @return object|bool
		 */
		public function extractArchives( $extract=true ) {
			if ( $extract ) {
				if ( zula_supports( 'zipExtraction' ) ) {
					$this->config['extractArchives'] = true;
					$this->config['allowedMime'][] = 'application/zip';
				}
				if ( zula_supports( 'tarExtraction' ) ) {
					$this->config['extractArchives'] = true;
					$this->config['allowedMime'][] = 'application/x-tar';
				}
				return $this->config['extractArchives'] ? $this : false;
			} else {
				$this->config['extractArchives'] = false;
				return $this;
			}
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
				return new Uploader_File( $this->files[ $key ], $this );
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
