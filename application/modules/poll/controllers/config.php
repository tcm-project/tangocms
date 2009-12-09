<?php
// $Id: config.php 2798 2009-11-24 12:15:41Z alexc $

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

	class Poll_controller_config extends Zula_ControllerBase {

		/**
		 * Amount of Polls to display per page
		 */
		const _PER_PAGE = 12;

		/**
		 * Constructor
		 * Sets the common page links
		 */
		public function __construct( $moduleDetails, $config, $sector ) {
			parent::__construct( $moduleDetails, $config, $sector );
			$this->setPageLinks( array(
										t('Manage Polls') 	=> $this->_router->makeUrl( 'poll', 'config' ),
										t('Add Poll')		=> $this->_router->makeUrl( 'poll', 'config', 'add'),
										));
		}

		/**
		 * Displays all polls (with pagination), or deletes the selected
 		 *
		 * @return string|bool
		 */
		public function indexSection() {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('Manage Polls') );
			$this->setOutputType( self::_OT_CONFIG );
			if ( $this->_input->checkToken() ) {
				// Attempt to delete all selected polls
				if ( !$this->_acl->check( 'poll_delete' ) ) {
					throw new Module_NoPermission;
				}
				try {
					$pollIds = $this->_input->post( 'poll_ids' );
					$count = 0;
					foreach( $pollIds as $pid ) {
						// check if user has permission to the poll
						$aclResource = 'poll-'.$pid;
						if ( $this->_acl->resourceExists( $aclResource ) && $this->_acl->check( $aclResource ) ) {
							$this->_model()->deletePoll( $pid );
							++$count;
						}
					}
					if ( $count > 0 ) {
						$this->_event->success( t('Deleted selected polls') );
					}
				} catch ( Input_KeyNoExist $e ) {
					$this->_event->error( t('No polls selected') );
				}
				return zula_redirect( $this->_router->makeUrl( 'poll', 'config' ) );
			} else if ( $this->_acl->checkMulti( array('poll_add', 'poll_edit', 'poll_delete') ) ) {
				// Display certain amount of polls
				try {
					$curPage = abs( $this->_input->get('page')-1 );
				} catch ( Input_KeyNoExist $e ) {
					$curPage = 0;
				}
				$polls = $this->_model()->getAllPolls( self::_PER_PAGE, ($curPage*self::_PER_PAGE) );
				$pollCount = $this->_model()->getCount();
				if ( $pollCount > 0 ) {
					$pagination = new Pagination( $pollCount, self::_PER_PAGE );
				}
				$view = $this->loadView( 'config/overview.html' );
				$view->assign( array(
									'POLLS' => $polls,
									'COUNT'	=> $pollCount,
									));
				$view->assignHtml( array(
										'PAGINATION'	=> isset($pagination) ? $pagination->build() : '',
										'CSRF'			=> $this->_input->createToken( true ),
										));
				return $view->getOutput();
			} else {
				throw new Module_NoPermission;
			}
		}

		/**
		 * Displays and handles adding of a new poll
		 *
		 * @return string|bool
		 */
		public function addSection() {
			if ( !$this->_acl->check( 'poll_add' ) ) {
				throw new Module_NoPermission;
			}
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('Add Poll') );
			$this->setOutputType( self::_OT_CONFIG );
			// Get and check if form is valid
			$form = $this->buildPollForm();
			if ( $form->hasInput() && $form->isValid() ) {
				$fd = $form->getValues( 'poll' );
				$pid = $this->_model()->addPoll( $fd['title'], $fd['duration'], $fd['options'] );
				$this->_event->success( t('Added poll') );
				// Update the ACL resource
				try {
					$roles = $this->_input->post( 'acl_resources/poll-' );
				} catch ( Input_KeyNoExist $e ) {
					$roles = array();
				}
				$this->_acl->allowOnly( 'poll-'.$pid, $roles );
				return zula_redirect( $this->_router->makeUrl( 'poll', 'config' ) );
			}
			$this->addAsset( 'js/options.js' );
			return $form->getOutput();
		}

		/**
		 * Handles editing an existing poll
		 *
		 * @return string
		 */
		public function editSection() {
			if ( !$this->_acl->check( 'poll_edit' ) ) {
				throw new Module_NoPermission;
			}
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('Edit Poll') );
			$this->setOutputType( self::_OT_CONFIG );
			try {
				$pid = $this->_router->getArgument( 'id' );
				$poll = $this->_model()->getPoll( $pid );
				// Check if user has permission
				$aclResource = 'poll-'.$pid;
				if ( !$this->_acl->resourceExists( $aclResource ) || !$this->_acl->check( $aclResource ) ) {
					throw new Module_NoPermission;
				}
				$this->setTitle( sprintf( t('Edit Poll "%s"'), $poll['title'] ) );
				// Get and check if form is valid
				$form = $this->buildPollForm( $pid, $poll['title'], $poll['duration'],
											  $poll['status'], $this->_model()->getPollOptions($pid)
											);
				if ( $form->hasInput() && $form->isValid() ) {
					$fd = $form->getValues( 'poll' );
					$this->_model()->editPoll( $pid, $fd['title'], $fd['duration'], $fd['status'] );
					$this->_event->success( t('Edited poll') );
					// Update ACL resource
					try {
						$roles = $this->_input->post( 'acl_resources/poll-'.$pid );
					} catch ( Input_KeyNoExist $e ) {
						$roles = array();
					}
					$this->_acl->allowOnly( 'poll-'.$pid, $roles );
				} else {
					return $form->getOutput();
				}
			} catch ( Router_ArgNoExist $e ) {
			} catch ( Poll_NoExist $e ) {
				$this->_event->error( t('Poll does not exist') );
			}
			return zula_redirect( $this->_router->makeUrl( 'poll', 'config' ) );
		}

		/**
		 * Builds the form for adding or editing a poll
		 *
		 * @param int $pid
		 * @param string $title
		 * @param int $duration
		 * @param string $status
		 * @param array $options
		 * @return object
		 */
		protected function buildPollForm( $pid=null, $title=null, $duration=1, $status='active', $options=array() ) {
			$op = $pid === null ? 'add' : 'edit';
			$form = new View_form( 'config/form_poll.html', 'poll' );
			$form->addElement( 'poll/title', $title, t('Title'), new Validator_Length(1, 255) );
			$form->addElement( 'poll/duration', $duration, t('Duration'), new Validator_Between(0, 7) );
			$form->addElement( 'poll/status', $status, t('Status'), new Validator_InArray( array('active', 'closed') ) );
			if ( $op == 'add' ) {
				$form->addElement( 'poll/options', $options, t('Options'), new Validator_Between(2, 10) );
				try {
					foreach( $this->_input->post( 'poll/options' ) as $key=>$tmpOpt ) {
						$form->addElement( 'poll/options/'.$key, $key, sprintf( t('Option %1$d'), $key+1 ), new Validator_Length(1, 255) );
					}
				} catch ( Input_KeyNoExist $e ) {
				}
			} else {
				$form->assign( array('poll' => array('options' => $options)) );
			}
			$form->assign( array(
								'OP'		=> $op,
								'ID'		=> $pid,
								));
			$form->assignHtml( array(
								'ACL_FORM'	=> $this->_acl->buildForm( array(t('View poll') => 'poll-'.$pid) ),
								));
			return $form;
		}

		/**
		 * Adds a new poll option to a poll
		 *
		 * @return string|bool
		 */
		public function addOptSection() {
			if ( !$this->_acl->check( 'poll_edit' ) ) {
				throw new Module_NoPermission;
			}
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('Add Option') );
			$this->setOutputType( self::_OT_CONFIG );
			// Get which poll to add the option to
			try {
				$pid = $this->_router->getArgument( 'id' );
				$poll = $this->_model()->getPoll( $pid );
				// check if user has permission to the poll
				$aclResource = 'poll-'.$poll['id'];
				if ( !$this->_acl->resourceExists( $aclResource ) || !$this->_acl->check( $aclResource ) ) {
					throw new Module_NoPermission;
				}
				// Build form and check it is valid
				$form = $this->buildOptionForm();
				if ( $form->hasInput() && $form->isValid() ) {
					$this->_model()->addOption( $poll['id'], $form->getValues( 'poll/title' ) );
					$this->_event->success( t('Added poll option') );
					return zula_redirect( $this->_router->makeUrl( 'poll', 'config', 'edit', null, array('id' => $poll['id']) ) );
				}
				return $form->getOutput();
			} catch ( Router_ArgNoExist $e ) {
				$this->_event->error( t('No poll selected') );
			} catch ( Poll_NoExist $e ) {
				$this->_event->error( t('Poll does not exist') );
			}
			return zula_redirect( $this->_router->makeUrl( 'poll', 'config' ) );
		}

		/**
		 * Edits an existing poll option for a poll (only if user has permission
		 * to the parent poll.)
		 *
		 * @return string|bool
		 */
		public function editOptSection() {
			if ( !$this->_acl->check( 'poll_edit' ) ) {
				throw new Module_NoPermission;
			}
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('Edit Poll Option') );
			$this->setOutputType( self::_OT_CONFIG );
			// Get which option we are to edit
			try {
				$optionId = $this->_router->getArgument( 'id' );
				$option = $this->_model()->getOption( $optionId );
				// check user permission
				$aclResource = 'poll-'.$option['poll_id'];
				if ( !$this->_acl->resourceExists( $aclResource ) || !$this->_acl->check( $aclResource ) ) {
					throw new Module_NoPermission;
				}
				$form = $this->buildOptionForm( $option['title'], $option['id'] );
				if ( $form->hasInput() && $form->isValid() ) {
					$this->_model()->editOption( $option['id'], $form->getValues( 'poll/title' ) );
					$this->_event->success( t('Edited poll option') );
					return zula_redirect( $this->_router->makeUrl( 'poll', 'config', 'edit', null, array('id' => $option['poll_id']) ) );
				}
				return $form->getOutput();
			} catch ( Router_ArgNoExist $e ) {
				$this->_event->error( t('No option selected') );
			} catch ( Poll_OptionNoExist $e ) {
				$this->_event->error( t('Option does not exist') );
			}
			return zula_redirect( $this->_router->makeUrl( 'poll', 'config' ) );
		}

		/**
		 * Builds the form for adding or editing a poll option
		 *
		 * @param string $title
		 * @param int $optionId
		 * @return object
		 */
		protected function buildOptionForm( $title=null, $optionId=null ) {
			$op = is_null($optionId) ? 'add' : 'edit';
			$form = new View_form( 'config/form_option.html', 'poll', ($op == 'add') );
			$form->addElement( 'poll/title', $title, t('Title'), new Validator_Length(1, 255) );
			$form->assign( array(
								'ID'		=> $optionId,
								'OP'		=> $op,
								));
			return $form;
		}

		/**
		 * Deletes all selected poll options
		 *
		 * @return string
		 */
		public function delOptSection() {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setOutputType( self::_OT_CONFIG );
			if ( !$this->_acl->check( 'poll_delete' ) ) {
				throw new Module_NoPermission;
			} else if ( !$this->_input->checkToken() ) {
				$this->_event->error( Input::csrfMsg() );
			} else {
				try {
					$poll = $this->_model()->getPoll( $this->_router->getArgument('id') );
					// Check user has permission
					$resource = 'poll-'.$poll['id'];
					if ( $this->_acl->resourceExists( $resource ) && $this->_acl->check( $resource ) ) {
						$optionIds = $this->_input->post( 'option_ids' );
						foreach( (array) $optionIds as $oid ) {
							try {
								$this->_model()->deleteOption( $oid );
							} catch ( Poll_OptionNoExist $e  ) {
							}
						}
						$this->_event->success( t('Deleted selected options') );
					} else {
						throw new Module_NoPermission;
					}
				} catch ( Input_KeyNoExist $e ) {
					$this->_event->error( t('No options selected') );
				}
			}
			if ( isset( $poll['id'] ) ) {
				return zula_redirect( $this->_router->makeUrl( 'poll', 'config', 'edit', null, array('id' => $poll['id']) ) );
			} else {
				return zula_redirect( $this->_router->makeUrl( 'poll', 'config' ) );
			}
		}

	}

?>
