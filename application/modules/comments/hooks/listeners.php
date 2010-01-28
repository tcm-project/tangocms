<?php

/**
 * Zula Framework Module (Comments)
 * --- Hooks file for listning to possible events
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Comments
 */

	class Comments_Hooks extends Zula_HookBase {

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
		 * hook: module_output_bottom
		 * Show comments and comments form
		 *
		 * @param array $msc
		 * @param int $contentType
		 * @param string $sector
		 * @param string $title
		 * @return mixed
		 */
		public function hookModuleOutputBottom( array $mcs, $contentType, $sector, $title ) {
			if ( 
				$sector == 'SC' && $contentType & Zula_ControllerBase::_OT_CONTENT_DYNAMIC &&
				!($contentType & Zula_ControllerBase::_OT_CONFIG)
			) {
				$requestPath = $this->_router->getRequestPath( Router::_TRIM_ALL );
				$view = new View( 'display/linear.html', 'comments' );
				$view->assign( array('TITLE' => $title) );
				$view->assignHtml( array(
										'COMMENTS'	=> $this->_model( 'comments', 'comments' )->get($requestPath),
									));
				if ( $this->_acl->check( 'comments_post' ) ) {
					/**
					 * Store the hash path as a valid comment path, then build the
					 * form view and output both views
					 */
					$hashPath = zula_hash( $requestPath );
					$_SESSION['comments'][ $hashPath ] = array(
																			'path'		=> $requestPath,
																			'siteType'	=> $this->_router->getSiteType(),
																			);
					$form = new View( 'form.html', 'comments' );
					$form->assign( array(
										'comments' => array( # Add dummy values
															'hash'	=> $hashPath,
															'name'	=> $this->_session->getUser( 'username' ),
															'website' => null,
															'body' 	=> null,
															),
										));
					// Antispam/Captcha
					$antispam = new Antispam;
					$form->assignHtml( array(
											'CSRF' 		=> $this->_input->createToken(true),
											'ANTISPAM'	=> $antispam->create(),
											));
					return $view->getOutput().$form->getOutput();
				} else {
					return $view->getOutput();
				}
			}
		}

	}

?>
