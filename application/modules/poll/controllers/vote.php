<?php

/**
 * Zula Framework Module (poll)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Poll
 */

	require_once 'base.php';

	class Poll_controller_vote extends PollBase {

		/**
		 * Places a vote on a poll if the user has permission
		 *
		 * @return bool
		 */
		public function indexSection() {
			$this->_locale->textDomain( $this->textDomain() );
			try {
				$oid = $this->_input->post( 'poll/oid' );
				$option = $this->_model()->getOption( $oid );
				$poll = $this->_model()->getPoll( $option['poll_id'] );
				// Check user has permission
				$aclResource = 'poll-'.$poll['id'];
				if ( !$this->_acl->resourceExists( $aclResource ) || !$this->_acl->check( $aclResource ) ) {
					throw new Module_NoPermission;
				} else if ( $this->isClosed( $poll ) ) {
					$this->_event->error( t('Sorry, the poll is now closed') );
				} else if ( $this->hasUserVoted( $this->_model()->getPollVotes($poll['id']) ) ) {
					$this->_event->error( t('Sorry, you have already voted') );
				} else {
					$this->_model()->vote( $option['id'] );
					$this->_event->success( t('Your vote has been placed!') );
				}
				return zula_redirect( $this->_router->makeUrl( 'poll', 'view', $poll['id'] ) );
			} catch ( Input_KeyNoExist $e ) {
				$this->_event->error( t('No option selected') );
			} catch ( Poll_OptionNoExist $e ) {
				$this->_event->error( t('Invalid option selected') );
			} catch ( Poll_NoExist $e ) {
				$this->_event->error( t('Poll does not exist') );
			}
			return zula_redirect( $_SESSION['previous_url'] );
		}

	}

?>