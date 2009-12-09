<?php

/**
 * Zula Framework Event Feedback
 * Stores different success and error messages into a session array for later use
 * Allows controllers to register an event, such as an error, or succsess message
 * that needs to be displayed to the user. Such as 'Added Menu Item'.
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Event
 */

	class Event extends Zula_LibraryBase {

		/**
		 * Constants to use when displaying the events
		 * so you can limit which are actually shown
		 */
		const
			_SHOW_ERROR 	= 1,
			_SHOW_SUCCESS 	= 2,
			_SHOW_INFO		= 4,
			_SHOW_ALL 		= 7;

		/**
		 * Event types to be used
		 * @var array
		 */
		protected $eventTypes = array( 'error', 'success', 'info' );

		/**
		 * Constructor function
		 *
		 * @return object
		 */
		public function __construct() {
			foreach( $this->eventTypes as $type ) {
				if ( !isset( $_SESSION['event_feedback'][ $type ] ) || !is_array( $_SESSION['event_feedback'][ $type ] ) ) {
					$_SESSION['event_feedback'][ $type ] = array();
				}
			}
		}

		/**
		 * Handles the storing of all event messages.
		 *
		 * @param string $type
		 * @param string $msg
		 * @return bool
		 */
		protected function handle( $name, $msg ) {
			if ( $msg && !_AJAX_REQUEST ) {
				$this->_log->message( '('.$name.') '.$msg, Log::L_EVENT );
				$_SESSION['event_feedback'][ $name ][] = zula_htmlspecialchars( $msg );
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Handle for 'error' events
		 *
		 * @param string $msg
		 * @return bool
		 */
		public function error( $msg ) {
			return $this->handle( 'error', $msg );
		}

		/**
		 * Handle for 'success' events
		 *
		 * @param string $msg
		 * @return bool
		 */
		public function success( $msg ) {
			return $this->handle( 'success', $msg );
		}

		/**
		 * Handle for 'info' events
		 *
		 * @param string $msg
		 * @return bool
		 */
		public function info( $msg ) {
			return $this->handle( 'info', $msg );
		}

		/**
		 * Outputs the event messages stored (by default it will return
		 * all of the event types.)
		 *
		 * @param int $filter		Filter which event types to show
		 * @return string
		 */
		public function output( $filter=self::_SHOW_ALL ) {
			$output = '';
			foreach( $this->eventTypes as $type ) {
				$constName = 'Event::_SHOW_'.strtoupper( $type );
				if ( defined( $constName ) && ($filter & constant( $constName )) && !empty( $_SESSION['event_feedback'][ $type ] ) ) {
					$messages = array_unique( $_SESSION['event_feedback'][ $type ] );
					$output .= '<div id="eventmsg" class="'.$type.'">'.zula_implode_adv( $messages, '<p>', '</p>' ).'</div>';
					// Remove the old messages since they are no longer needed
					$_SESSION['event_feedback'][ $type ] = array();
				}
			}
			return $output;
		}

	}

?>
