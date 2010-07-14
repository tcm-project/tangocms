<?php

/**
 * Zula Framework Antispam ReCaptcha
 * --- Custom implementation of the recaptchalib (http://recaptcha.net/plugins/php/)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Antispam
 */

	class Antispam_Recaptcha extends Zula_LibraryBase implements Antispam_Interface {

		/**
		 * Public Key
		 * @var string
		 */
		protected $publicKey = null;

		/**
		 * Private Key
		 * @var string
		 */
		protected $privateKey = null;

		/**
		 * Constructor
		 * Sets up the private/public key
		 *
		 * @return object
		 */
		public function __construct() {
			$this->publicKey = $this->_config->get( 'antispam/recaptcha/public' );
			$this->privateKey = $this->_config->get( 'antispam/recaptcha/private' );
		}

		/**
		 * Create the necessary form elements, and return a string to embed
		 *
		 * @return string
		 */
		public function create() {
			if ( !trim( $this->publicKey ) ) {
				throw new Antispam_Exception( 'reCAPTCHA API public key needed' );
			}
			$server = $this->_router->getScheme() == 'https' ? 'https://api-secure.recaptcha.net' : 'http://api.recaptcha.net';
			return '<script type="text/javascript" src="'.$server.'/challenge?k='.$this->publicKey.'"></script>
					<noscript>
						<iframe src="'.$server.'/noscript?k='.$this->publicKey.'" height="300" width="500" frameborder="0"></iframe><br/>
						<textarea name="recaptcha_challenge_field" rows="3" cols="40"></textarea>
						<input type="hidden" name="recaptcha_response_field" value="manual_challenge"/>
					</noscript>';
		}

		/**
		 * Check if the Antispam method has passed
		 *
		 * @return bool
		 */
		public function check() {
			if ( !trim( $this->privateKey ) ) {
				throw new Antispam_Exception( 'reCAPTCHA API private key needed' );
			} else if ( empty( $_SERVER['REMOTE_ADDR'] ) ) {
				throw new Antispam_Exception( 'unable to gather remote address for reCAPTCHA' );
			}
			try {
				$challenge = $this->_input->post( 'recaptcha_challenge_field' );
				$response = $this->_input->post( 'recaptcha_response_field' );
				if ( trim( $challenge ) && trim( $response ) ) {
					// Create an HTTP POST request to the reCAPTCHA server
					if ( ($sock = fsockopen('api-verify.recaptcha.net', 80, $errno, $errstr, 10)) == false ) {
						throw new Antispam_Exception( 'unable to create socket' );
					}
					$data = http_build_query( array(
													'privatekey'	=> $this->privateKey,
													'remoteip'		=> $_SERVER['REMOTE_ADDR'],
													'challenge'		=> $challenge,
													'response'		=> $response,
													)
											);
					$httpRequest = "POST /verify HTTP/1.0\r\n".
								   "Host: api-verify.recaptcha.net\r\n".
								   "Content-Type: application/x-www-form-urlencoded;\r\n".
								   "Content-Length: ".strlen($data)."\r\n".
								   "User-Agent: reCAPTCHA/PHP\r\n".
								   "\r\n$data";
					fwrite( $sock, $httpRequest );
					$response = '';
					while( !feof( $sock ) ) {
						$response .= fgets( $sock, 1160 ); # One TCP-IP packet
					}
					fclose( $sock );
					$response = explode( "\r\n\r\n", $response, 2 );
					// Check what the answer was
					$answers = explode( "\n", $response[1] );
					return trim( $answers[0] ) === 'true';
				}
				return false;
			} catch ( Input_KeyNoExist $e ) {
				throw new Antispam_Exception( 'missing recaptcha input data, strange.' );
			}
		}

	}

?>
