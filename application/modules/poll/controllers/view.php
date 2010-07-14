<?php

/**
 * Zula Framework Module (poll)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Poll
 */

	require_once 'base.php';

	class Poll_controller_view extends PollBase {

		/**
		 * Allows for shorter URLs, viewing by a given poll ID
		 *
		 * @param string $name
		 * @param array $args
		 * @return mixed
		 */
		public function __call( $name, $args ) {
			return $this->displayPoll( substr($name, 0, -7) );
		}

		/**
		 * Displays the latest poll that exists
		 *
		 * @return mixed
		 */
		public function indexSection() {
			$polls = $this->_model()->getAllPolls( 1 );
			if ( empty( $polls ) ) {
				return false;
			} else {
				$poll = reset( $polls );
				return $this->displayPoll( $poll['id'] );
			}
		}

		/**
		 * Displays a random poll
		 *
		 * @return mixed
		 */
		public function randomSection() {
			$polls = array();
			foreach( $this->_model()->getAllPolls() as $tmpPoll ) {
				if ( $tmpPoll['status'] == 'active' ) {
					$polls[] = $tmpPoll;
				}
			}
			return empty($polls) ? false : $this->displayPoll( $polls[ array_rand($polls) ]['id'] );
		}

		/**
		 * Builds up the view to display the poll. If being loaded within
		 * the special 'SC' sector, additional details will be displayed.
		 *
		 * @param int $pid
		 * @return string
		 */
		protected function displayPoll( $pid ) {
			try {
				$poll = $this->_model()->getPoll( $pid );
				// Check permission
				$aclResource = 'poll-'.$poll['id'];
				if ( !$this->_acl->resourceExists( $aclResource ) || !$this->_acl->check( $aclResource ) ) {
					throw new Module_NoPermission;
				}
				$pollClosed = $this->isClosed( $poll );
				$this->setTitle( sprintf( ($pollClosed ? t('%s (Closed)') : '%s'), $poll['title'] ) );
				// Get all options, votes and see if user has already voted on this poll
				$options = $this->_model()->getPollOptions( $poll['id'] );
				$votes = $this->_model()->getPollVotes( $poll['id'] );
				$votedOn = $this->hasUserVoted( $votes ); # Option which the current user has voted on (if any).
				if ( $votedOn || $pollClosed ) {
					/**
					 * Display only the results to the user
					 */
					$totalVotes = 0;
					foreach( $votes as $val ) {
						$totalVotes += count( $val );
					}
					// Insert how many votes and the percentage there is for each option
					foreach( $options as $key=>$tmpOpt ) {
						$options[ $key ]['votes'] = isset($votes[ $tmpOpt['id'] ]) ? count($votes[ $tmpOpt['id'] ]) : 0;
						$pct = $options[ $key ]['votes'] > 0 ? ($options[ $key ]['votes'] / $totalVotes) * 100 : 0;
						$options[ $key ]['percent'] = round( $pct, 2 );
					}
					$view = $this->loadView( 'view/results.html' );
					$view->assign( array(
										'VOTED_ON'		=> $votedOn,
										'TOTAL_VOTES'	=> $totalVotes,
										'SECTOR'		=> $this->getSector(),
										));
				} else {
					/**
					 * Display the form to vote on the poll.
					 */
					$view = new View_form( 'view/vote.html', 'poll' );
					$view->action( $this->_router->makeUrl( 'poll', 'vote' ) );
				}
				$view->assign( array(
									'POLL'		=> $poll,
									'OPTIONS'	=> $options,
									));
				return $view->getOutput();
			} catch ( Poll_NoExist $e ) {
				throw new Module_ControllerNoExist;
			}
		}

	}

?>
