<?php
/*
 * Comments Config
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009, Robert Clipsham
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Comments
 */

	class Comments_controller_config extends Zula_ControllerBase {

		/**
		 * Number of comments to display per-page
		 */
		const _PER_PAGE = 12;

		/**
		 * Constructor
		 * Sets common page links
		 *
		 * @return object
		 */
		public function __construct( $moduleDetails, $config, $sector ) {
			parent::__construct( $moduleDetails, $config, $sector );
			$this->setPageLinks( array(
									t('Manage comments')	=> $this->_router->makeUrl( 'comments', 'config' ),
									t('Settings')			=> $this->_router->makeUrl( 'comments', 'config', 'settings' ),
									));
		}

		/**
		 * Shows comments that can be managed (Edited/deleted etc). Also handles
		 * deleting or approving of selected comments.
		 *
		 * @return string
		 */
		public function indexSection() {
			$this->setTitle( t('Manage comments') );
			$this->setOutputType( self::_OT_CONFIG );
			if ( !$this->_acl->check( 'comments_manage' ) ) {
				throw new Module_NoPermission;
			}
			if ( $this->_input->checkToken() ) {
				try {
					$commentIds = $this->_input->post( 'comments/ids' );
					if ( $this->_input->has( 'post', 'comments/approve' ) ) {
						foreach( $commentIds as $cid ) {
							$this->_model()->edit( $cid, array('status' => 'accepted') );
						}
						$msg = t('Approved selected comments');
					} else {
						$this->_model()->delete( $commentIds );
						$msg = t('Deleted selected comments');
					}
					$this->_event->success( $msg );
				} catch ( Input_KeyNoExist $e ) {
					$this->_event->error( t('No comments selected') );
				}
				return zula_redirect( $this->_router->makeUrl( 'comments', 'config' ) );
			} else {
				try {
					$type = $this->_input->get( 'type' );
				} catch ( Input_KeyNoExist $e ) {
					$type = $this->_config->get( 'comments/moderate' ) ? 'moderation' : 'all';
				}
				// Display the correct comments respecting pagination and build form
				try {
					$curPage = abs( $this->_input->get( 'page' )-1 );
				} catch ( Input_KeyNoExist $e ) {
					$curPage = 0;
				}
				$comments = $this->_model()->get( null, $type, self::_PER_PAGE, self::_PER_PAGE*$curPage, 'DESC' );
				$commentsCount = $this->_model()->getCount();
				if ( $commentsCount > 0 ) {
					$pagination = new Pagination( $commentsCount, self::_PER_PAGE );
				}
				$view = $this->loadView( 'config/main.html' );
				$view->assign( array(
									'count' => $commentsCount,
									'type'	=> $type,
									));
				$view->assignHtml( array(
										'pagination'	=> isset($pagination) ? $pagination->build() : '',
										'csrf'			=> $this->_input->createToken( true ),
										'comments'		=> $comments,
										));
				return $view->getOutput();
			}
		}

		/**
		 * Manages settings for comments
		 *
		 * @return string|bool
		 */
		public function settingsSection() {
			$this->setTitle( t('Comments settings') );
			$this->setOutputType( self::_OT_CONFIG );
			if ( !$this->_acl->check( 'comments_manage' ) ) {
				throw new Module_NoPermission;
			}
			// Build form and validation
			$form = new View_form( 'config/settings.html', 'comments' );
			$form->action( $this->_router->makeUrl( 'comments', 'config', 'settings' ) );
			$form->addElement( 'comments/moderate',
							   $this->_config->get( 'comments/moderate' ),
							   t('Moderate comments'),
							   new Validator_Bool
							  );
			if ( $form->hasInput() && $form->isValid() ) {
				foreach( $form->getValues( 'comments' ) as $key=>$val ) {
					$this->_config_sql->update( 'comments/'.$key, $val );
				}
				$this->_event->success( t('Updated comment settings') );
				return zula_redirect( $this->_router->makeUrl( 'comments', 'config', 'settings' ) );
			}
			return $form->getOutput();
		}

	}

?>
