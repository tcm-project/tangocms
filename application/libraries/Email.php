<?php

/**
 * Zula Framework Email
 * --- Wrapper for Swift Mailer, which handles the connection (transport)
 * and sending of emails.
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, 2009, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Email
 */

	require_once Registry::get( 'zula' )->getDir( '3rd_party' ).'/swiftmailer/lib/swift_required.php';

	class Email extends Zula_LibraryBase {

		/**
		 * Transport details to use
		 * @var array
		 */
		protected $transport = array(
									'type'		=> 'smtp',
									'smtp'		=> array(
														'host'			=> 'localhost',
														'port'			=> 25,
														'username'		=> null,
														'password'		=> null,
														'encryption'	=> null,
														),
									'sendmail'	=> '/usr/sbin/sendmail -bs',
									);
		/**
		 * Mailer that is to be used
		 * @var object
		 */
		static protected $mailer = array();

		/**
		 * Constructor
		 * Sets up the transport used to send email.
		 *
		 * @param object $transport
		 * @return object
		 */
		public function __construct( $transport=null ) {
			if ( $transport instanceof Swift_Transport ) {
				$this->transport = $transport;
			} else {
				foreach( array_filter( $this->_config->get('mail') ) as $key=>$val ) {
					if ( strpos( $key, 'smtp_' ) === 0 ) {
						$this->transport['smtp'][ substr( $key, 5 ) ] = $val;
					} else {
						$this->transport[ $key ] = $val;
					}
				}
				switch ( strtolower($this->transport['type']) ) {
					case 'smtp':
						$smtp = $this->transport['smtp'];
						$this->transport = new Swift_SmtpTransport( $smtp['host'], $smtp['port'], $smtp['encryption'] );
						$this->transport->setUsername( $smtp['username'] )
										->setPassword( $smtp['password'] );
						break;

					case 'sendmail':
						$this->transport = new Swift_SendmailTransport( $this->transport['sendmail'] );
						break;

					case 'mail':
						$this->transport = new Swift_MailTransport;
						break;

					default:
						throw new Email_UnknownTransport( 'email transport "'.$this->transport['type'].'" is unknown' );
				}
			}
		}

		/**
		 * Passes methods onto the Swift Transport instance, so developers
		 * can take full advantage of Swift Mailer directly
		 *
		 * @param string $name
		 * @param aray $args
		 * @return mixed
		 */
		public function __call( $name, $args ) {
			if ( is_callable( array($this->transport, $name) ) ) {
				return call_user_func_array( array($this->transport, $name), $args );
			}
		}

		/**
		 * Clears the mailer to force a new connection
		 *
		 * @return object
		 */
		public function useNewConnection() {
			self::$mailer = null;
			return $this;
		}

		/**
		 * Gets the mailer (or makes a new one)
		 *
		 * @return object
		 */
		protected function getMailer() {
			if ( !(self::$mailer instanceof Swift_Mailer) ) {
				self::$mailer = new Swift_Mailer( $this->transport );
			}
			return self::$mailer;
		}

		/**
		 * Sends off the message, though checks if we are using Email_Message
		 * as the object first
		 *
		 * @param object $message
		 * @param array $failedRecipients
		 * @return int
		 */
		public function send( $message, &$failedRecipients=null ) {
			if ( $message instanceof Email_Message ) {
				$message = $message->getMessage();
			}
			if ( $numSent = $this->getMailer()->send($message, $failedRecipients) ) {
				return $numSent;
			} else {
				throw new Email_Exception( '0 emails sent successfully, possible error' );
			}
		}
		
		/**
		 * Batch sends emails to many recipients
		 *
		 * @param object $message
		 * @param array $failedRecipients
		 * @return int
		 */
		public function batchSend( $message, &$failedRecipients=null ) {
			if ( $message instanceof Email_Message ) {
				$message = $message->getMessage();
			}
			if ( $numSent = $this->getMailer()->batchSend($message, $failedRecipients) ) {
				return $numSent;
			} else {
				throw new Email_Exception( '0 emails sent successfully, possible error' );
			}
		}

	}

?>
