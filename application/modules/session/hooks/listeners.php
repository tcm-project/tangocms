<?php

/**
 * Zula Framework Module
 * Hooks file for listening to possible events
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Session
 */

	class Session_Hooks extends Zula_HookBase {

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
		 * hook: cntrlr_pre_dispatch
		 *
		 * @return array|null
		 */
		public function hookCntrlrPreDispatch( $rData ) {
			if (
				!empty( $_SESSION['mod']['session']['changePw'] ) && ($rData['module'] != 'session' ||
				($rData['module'] == 'session' && $rData['controller'] != 'index' && $rData['section'] != 'logout'))
			) {
				$rData = array(
							'module'		=> 'session',
							'controller'	=> 'pwd',
							'section'		=> 'expire',
							'config'		=> array('displayTitle' => true),
							);
			}
			return $rData;
		}

	}

?>
