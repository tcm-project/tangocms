<?php

/**
 * Zula Framework Captcha
 * --- Provides a common and complete way to offer Captcha into any form
 * to help protect against bots and unwanted spam
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Captcha
 */

	class Antispam_captcha extends Zula_LibraryBase implements Antispam_Interface {

		/**
		 * Directory where captcha images are stored
		 * @var string
		 */
		protected $tmpDir = null;

		/**
		 * Height of the captcha (px)
		 * @var integer
		 */
		protected $height = 40;

		/**
		 * Width of the captcha (px)
		 * @var integer
		 */
		protected $width = 200;

		/**
		 * ID of the current captcha
		 * @var integer
		 */
		protected $captchaId = null;

		/**
		 * Font that we will be using for the captcha
		 * @var string
		 */
		protected $font = 'VeraSe.ttf';

		/**
		 * How long a captcha imaage should last (in seconds) before
		 * being removed, thus causing the captcha to be invalid
		 * @var integer
		 */
		protected $timeout = 600; # 10 mins

		/**
		 * Colors that the captcha image should use
		 * @var array
		 */
		protected $colors = array(
								'bg' 	=> array(255, 255, 255),
								'font' 	=> array(150, 150, 150),
								'noise' => array(183, 183, 183),
								);

		/**
		 * Constructor
		 * Sets up details for the captchas, and cleans up older images
		 *
		 * @param int $height
		 * @param int $width
		 * @return object
		 */
		public function __construct( $height=40, $width=200 ) {
			$this->tmpDir = $this->_zula->getDir( 'tmp' );
			// Set up the dimensions of the captcha image
			$this->height = ($height < 20) ? 40 : abs( $height );
			$this->width = ($width < 100) ? 200 : abs( $width );
			// Choose a random font to use for the text
			$this->fonts = glob( $this->_zula->getDir( 'fonts' ).'/captcha/*.ttf' );
			$this->font = $this->fonts[ array_rand( $this->fonts ) ];
			// Remove old timmed out images
			$images = glob( $this->tmpDir.'/captcha-*.png' );
			if ( is_array( $images ) ) {
				foreach( $images as $file ) {
					if ( (time() - filemtime( $file )) > $this->timeout ) {
						$filename = pathinfo( $file, PATHINFO_FILENAME );
						$captchaId = substr( $filename, strpos($filename, '-') );
						unset( $_SESSION['antispam']['captcha'][ $captchaId ] );
						if ( !@unlink( $file ) ) {
							$this->_log->message( 'Captcha::__construct() could not remove old captcha file', Log::L_WARNING );
						}
					}
				}
			}
		}

		/**
		 * Generates the security code that will be used within
		 * the captcha image, and also create the unique captch ID
		 *
		 * @return string
		 */
		protected function generateCode() {
			$chars = '23456789ABCDEFGHJKLMNOPQRSTUVWXYZ';
			$code = '';
			for( $i=0; $i <= 6; $i++ ) {
				// Code that will be on the captcha image
				$code .= substr( $chars, rand(0, strlen($chars)), 1 );
			}
			$code = substr( $code, 0, 6 );
			do {
				$captchaId = uniqid();
			} while (
				isset( $_SESSION['antispam']['captcha'][ $captchaId ] ) ||
				file_exists( $this->tmpDir.'/captcha-'.$captchaId.'.png' )
			);
			$_SESSION['antispam']['captcha'][ $captchaId ] = $code;
			$this->captchaId = $captchaId;
			return $code;
		}

		/**
		 * Creates the actual captcha image and stores it in the temp
		 * directory. Returns an array with details such as the captcha
		 * ID and the url to the captcha image.
		 *
		 * It will also create a nicely formatted form that you can use
		 * if you wish.
		 *
		 * @return array
		 */
		public function create() {
			if ( !function_exists( 'imagettfbbox' ) || !function_exists( 'imagettftext' ) ) {
				throw new Antispam_Exception( 'PHP extension "gd" is not currently laoded, or not compiled with FreeType library' );
			}
			$code = $this->generateCode();
			/**
			 * Attempt to create the true color image and set the font size and font face
			 */
			$image = imageCreateTrueColor( $this->width, $this->height );
			if ( $image === false ) {
				throw new Antispam_Exception( 'Captcha could not create true color image' );
			}
			// Allocate the image colors
			$colors = array();
			foreach( $this->colors as $type=>$color ) {
				$colors[ $type ] = imageColorAllocate( $image, $color[0], $color[1], $color[2] );
			}
			imagefill( $image, 0, 0, $colors['bg'] );
			$fontSize = $this->height * 0.60;
			/**
			 * Generate random dots and lines in the background to act as background noise
			 */
			for( $i=0; $i < ($this->width*$this->height)/3; $i++ ) {
				imagefilledellipse( $image,
									rand(0, $this->width),
									rand(0, $this->height),
									1, 1, $colors['noise']
								   );
			}
			for( $i=0; $i < ($this->width*$this->height)/150; $i++ ) {
				imageline( $image,
						   rand(0, $this->width),
						   rand(0, $this->height),
						   rand(0, $this->width),
						   rand(0, $this->height),
						   $colors['noise']
						 );
			}
			// Create textbox and add the captcha text code
			$x = -20;
			foreach( str_split( $code ) as $char ) {
				$rand = rand( -5, 5 );
				$textBox = imagettfbbox( $fontSize, $rand, $this->font, $char );
				$x += ($this->width-$textBox[4]) / 7;
				$y = ($this->height-$textBox[5]) / 2;
				if ( !imagettftext( $image, $fontSize, $rand, $x, $y, $colors['font'], $this->font, $char ) ) {
					throw new Antispam_Exception( 'Captcha could not do "imagettftext"' );
				}
			}
			/**
			 * Save the generated captcha image into the temp directory
			 */
			if ( zula_is_writable( $this->tmpDir ) ) {
				$truePath = $this->tmpDir.'/captcha-'.$this->captchaId.'.png';
				$relPath = $this->_zula->getDir( 'tmp', true ).'/captcha-'.$this->captchaId.'.png'; # Relative Path
				if ( imagepng( $image, $truePath ) ) {
					chmod( $truePath, 0664 );
				} else {
					throw new Antispam_Exception( 'Captcha failed to save png file "'.$truePath.'". Check permissions.' );
				}
				imagedestroy( $image );
				// Build the form view that can be used if wanted
				$view = new View( 'captcha.html' );
				$view->assign( array(
									'path'	=> $relPath,
									'id'	=> $this->captchaId,
									));
				return $view->getOutput();
			} else {
				throw new Antispam_Exception( 'Captcha directory "'.$this->tmpDir.'" is not writable' );
			}
		}

		/**
		 * Checks the code which was sent in the form with that of the correct security code
		 *
		 * @return bool
		 */
		public function check() {
			try {
				$post = $this->_input->post( 'captcha' );
				$captchaId = key( $post );
				$code = strtoupper( current($post) );
			} catch ( Input_KeyNoExist $e ) {
				return false;
			}
			if ( isset( $_SESSION['antispam']['captcha'][ $captchaId ] ) ) {
				$valid = ($_SESSION['antispam']['captcha'][ $captchaId ] == $code);
				// Remove the image file and unset session key
				unset( $_SESSION['antispam']['captcha'][ $captchaId ] );
				$imgPath = $this->_zula->getDir( 'tmp' ).'/captcha-'.$captchaId.'.png';
				if ( !@unlink( $imgPath ) ) {
					$this->_log->message( 'Captcha could not remove "'.$imgPath.'"', Log::L_WARNING );
				}
				return $valid;
			} else {
				return false;
			}
		}

	}

?>
