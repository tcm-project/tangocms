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
 * @copyright Copyright (C) 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Captcha
 */

	class Antispam_captcha extends Zula_LibraryBase implements Antispam_Interface {

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
		 *
		 * @return object
		 */
		public function __construct() {
			$this->fonts = glob( $this->_zula->getDir( 'fonts' ).'/captcha/*.ttf' );
			$this->font = $this->fonts[ array_rand( $this->fonts ) ];
			if ( count( $_SESSION['antispam']['captcha'] ) > 10 ) {
				// Limit the amount of stored captcha IDs to the last 10
				$_SESSION['antispam']['captcha'] = array_slice( $_SESSION['antispam']['captcha'], -10 );
			}
		}

		/**
		 * Generates the security code + unique captcha ID of the image and
		 * return the form to be displayed to the user. Please note that no
		 * image will be created in this method.
		 *
		 * @return string
		 */
		public function create() {
			if ( !function_exists( 'imagettfbbox' ) || !function_exists( 'imagettftext' ) ) {
				throw new Antispam_Exception( 'PHP extension "gd" is not currently laoded, or not compiled with FreeType library' );
			}
			$chars = '23456789ABCDEFGHJKLMNOPQRSTUVWXYZ';
			$code = '';
			for( $i=0; $i <= 6; $i++ ) {
				// Code that will be on the captcha image
				$code .= substr( $chars, rand(0, strlen($chars)), 1 );
			}
			$code = substr( $code, 0, 6 );
			do {
				$captchaId = uniqid();
			} while ( isset( $_SESSION['antispam']['captcha'][ $captchaId ] ) );
			$_SESSION['antispam']['captcha'][ $captchaId ] = $code;
			// Build the form to use
			$view = new View( 'captcha.html' );
			$view->assign( array(
								'url'	=> $this->_router->makeUrl( 'antispam', 'captcha', $captchaId ),
								'id'	=> $captchaId,
								));
			return $view->getOutput();
		}

		/**
		 * Generates and outputs a PNG image to the browser with a given string
		 * of text to display, setting all required headers. bool false will be
		 * returned if unable to output the image
		 *
		 * @param string $code
		 * @return bool
		 */
		public function outputPng( $code ) {
			$image = imagecreatetruecolor( 200, 40 );
			if ( $image === false ) {
				$this->_log->message( 'Captcha could not create true color image', Log::L_WARNING );
				return false;
			}
			$colors = array();
			foreach( $this->colors as $type=>$color ) {
				$colors[ $type ] = imagecolorallocate( $image, $color[0], $color[1], $color[2] );
			}
			imagefill( $image, 0, 0, $colors['bg'] );
			$fontSize = 40 * 0.60;
			/**
			 * Generate random dots and lines in the background to act as background noise
			 */
			for( $i=0; $i < (200*40)/3; $i++ ) {
				imagefilledellipse( $image, rand(0, 200), rand(0, 40), 1, 1, $colors['noise'] );
			}
			for( $i=0; $i < (200*40)/150; $i++ ) {
				imageline( $image, rand(0, 200), rand(0, 40), rand(0, 200), rand(0, 40), $colors['noise'] );
			}
			// Create textbox and add the captcha text code
			$x = -20;
			foreach( str_split( $code ) as $char ) {
				$rand = rand( -5, 5 );
				$textBox = imagettfbbox( $fontSize, $rand, $this->font, $char );
				$x += (200-$textBox[4]) / 7;
				$y = (40-$textBox[5]) / 2;
				if ( !imagettftext( $image, $fontSize, $rand, $x, $y, $colors['font'], $this->font, $char ) ) {
					return false;
				}
			}
			header( 'HTTP/1.1 200 OK' );
			header( 'Content-Type: image/png' );
			$status = imagepng( $image );
			imagedestroy( $image );
			return $status;
		}

		/**
		 * Check the code which was provided in the form with that of the
		 * correct security code.
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
				unset( $_SESSION['antispam']['captcha'][ $captchaId ] );
				return $valid;
			} else {
				return false;
			}
		}

	}

?>
