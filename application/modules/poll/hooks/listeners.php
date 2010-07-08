<?php

/**
 * Zula Framework Module (Poll)
 * --- Hooks file for listning to possible events
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Poll
 */

	class Poll_hooks extends Zula_HookBase {

		/**
		 * Constructor
		 * Calls the parent constructor to register the methods
		 *
		 * @return object
		 */
		public function __construct() {
			parent::__construct( $this );
		}

		/**
		 * Hook: poll_display_modes
		 * Gets all display modes that this module has
		 *
		 * @return array
		 */
		public function hookPollDisplayModes() {
			return array(
						'latest'	=> t('Latest', _PROJECT_ID.'-poll'),
						'single'	=> t('Single Poll', _PROJECT_ID.'-poll'),
						'random'	=> t('Random', _PROJECT_ID.'-poll'),
						);
		}

		/**
		 * Hook: poll_resolve_mode
		 * Resolves a given Controller, Section and config data to an
		 * avaible display mode offered.
		 *
		 * @param string $cntrlr
		 * @param string $sec
		 * @param array $config
		 * @return string
		 */
		public function hookPollResolveMode( $cntrlr, $sec, $config ) {
			switch( $sec ) {
				case 'index':
					return 'latest';
				case 'random':
					return 'random';
				default:
					return 'single';
			}
		}

		/**
		 * Hook: poll_display_mode_config
		 * Returns HTML (commonly a table) to configure a display mode
		 *
		 * @param string $mode
		 * @return string
		 */
		public function hookPollDisplayModeConfig( $mode ) {
			switch( $mode ) {
				case 'single':
					$polls = array();
					foreach( $this->_model()->getAllPolls() as $tmpPoll ) {
						if ( $tmpPoll['status'] == 'active' ) {
							$polls[ $tmpPoll['id'] ] = $tmpPoll;
						}
					}
					$view = new View( 'layout_edit/single_poll.html', 'poll' );
					$view->assign( array(
										'POLLS'		=> $polls,
										'CURRENT'	=> $this->_input->post( 'sec' ),
										));
					break;

				case 'random':
					$view = new View( 'layout_edit/random_poll.html', 'poll' );
					break;

				case 'latest':
				default:
					$view = new View( 'layout_edit/latest_poll.html', 'poll' );
			}
			return $view->getOutput();
		}

	}

?>
