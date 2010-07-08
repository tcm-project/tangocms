<?php

/**
 * Zula Framework Email
 * --- Wrapper for Swift Mailer Messages, which handles the creation of messages
 * and their attachments.
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Email
 */

	require_once Registry::get( 'zula' )->getDir( '3rd_party' ).'/swiftmailer/lib/swift_required.php';

	class Email_Message extends Zula_LibraryBase {

		/**
		 * Config details to use
		 * @var array
		 */
		protected $config = array(
								'outgoing'		=> null,
								'incoming'		=> null,
								'subject_prefix'=> true,
								'signature'		=> null,
								);

		/**
		 * Message instance that'll be used
		 * @var object
		 */
		protected $message = null;

		/**
		 * Constructor
		 * Creates a new message instance with the subject and
		 * body provided, if any.
		 *
		 * @param string $subject
		 * @param string $body
		 * @param string $contentType
		 * @param string $charset
		 * @return ojbect
		 */
		public function __construct( $subject=null, $body=null, $contentType=null, $charset=null ) {
			foreach( $this->_config->get( 'mail' ) as $key=>$val ) {
				if ( array_key_exists( $key, $this->config ) ) {
					$this->config[ $key ] = $val;
				}
			}
			$this->message = Swift_Message::newInstance( null, null, $contentType, $charset )
										  ->setFrom( $this->config['outgoing'] )
										  ->setReturnPath( $this->config['incoming'] );
			$this->setSubject( $subject );
			$this->setBody( $body, $contentType );
		}

		/**
		 * Passes methods onto the Swift MEssage instance, so developers
		 * can take full advantage of Swift Mailer directly
		 *
		 * @param string $name
		 * @param aray $args
		 * @return mixed
		 */
		public function __call( $name, $args ) {
			if ( is_callable( array($this->message, $name) ) ) {
				return call_user_func_array( array($this->message, $name), $args );
			}
		}

		/**
		 * Adds the signature (value from mail/signature) to the message body
		 *
		 * @param string $body
		 * @param string $mime
		 * @return string
		 */
		protected function appendSignature( $body, $mime ) {
			$tokens = array(
							'[SITE_TITLE]'	=> $this->_config->get( 'config/title' ),
							'[SITE_SLOGAN]'	=> $this->_config->get( 'config/slogan' ),
							);
			$signature = str_replace( array_keys($tokens), array_values($tokens), $this->config['signature'] );
			if ( $mime == 'text/html' ) {
				$signature = zula_nls2p( $signature );
			}
			return $body."\n".$signature;
		}

		/**
		 * Gets the message object
		 *
		 * @return object
		 */
		public function getMessage() {
			return $this->message;
		}

		/**
		 * Sets the subject for the message, replacing certain vars
		 * and adding a prefix to the subject (if set to)
		 *
		 * @param string $subject
		 * @param bool $prefix
		 * @return object
		 */
		public function setSubject( $subject, $prefix=true ) {
			if ( $prefix && $this->config['subject_prefix'] ) {
				$tokens = array(
								'[PAGE]'		=> $subject,
								'[SITE_TITLE]'	=> $this->_config->get( 'config/title' ),
								);
				$subject = str_replace( array_keys($tokens), array_values($tokens), $this->_config->get( 'config/title_format' ) );
			}
			$this->message->setSubject( $subject );
		}

		/**
		 * Sets the body, and will also add in an automagic signature
		 *
		 * @param string $body
		 * @param string $contentType
		 * @param bool $signature
		 * @return bool
		 */
		public function setBody( $body, $contentType=null, $signature=true ) {
			if ( $signature ) {
				$body = $this->appendSignature( $body, $contentType );
			}
			$this->message->setBody( $body, $contentType );
		}

		/**
		 * Adds a new part to the message, and can add in signature
		 *
		 * @param string $body
		 * @param string $contentType
		 * @param bool $signature
		 * @return bool
		 */
		public function addPart( $body, $contentType=null, $signature=true ) {
			if ( $signature ) {
				$body = $this->appendSignature( $body, $contentType );
			}
			$this->message->addPart( $body, $contentType );
		}

	}

?>
