<?php
/*
 * Edits an existing comment
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Comments
 */

	class Comments_controller_edit extends Zula_ControllerBase {

		/**
		 * Allow for shorter URLs
		 *
		 * @param string $name
		 * @param array $args
		 * @return mixed
		 */
		public function __call( $name, $args ) {
			return $this->indexSection( substr( $name, 0, -7 ) );
		}

		/**
		 * Shows form for editing an existing comment
		 *
		 * @param int $commentId
		 * @return bool|string
		 */
		public function indexSection( $commentId=null ) {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('Edit Comment') );
			// Check if we are editing 'inline' or via the main config cntrlr
			if ( $this->_router->hasArgument( 'inline' ) || $this->_input->has( 'post', 'comments_inline' ) ) {
				$editInline = true;
			} else {
				$editInline = false;
				$this->setPageLinks( array(
											t('Manage Comments')	=> $this->_router->makeUrl( 'comments', 'config' ),
											t('Settings')			=> $this->_router->makeUrl( 'comments', 'config', 'settings' ),
										));
			}
			try {
				if ( $commentId === null ) {
					$commentId = $this->_input->post( 'comments/id' );
				}
				$comment = $this->_model()->getDetails( $commentId );
			} catch ( Comments_NoExist $e ) {
				$this->_event->error( t('Unable to edit comment as it does not exist') );
				$url = $editInline ? $this->_router->getBaseUrl() : $this->_router->makeUrl( 'comments', 'config' );
				return zula_redirect( $url );
			}
			if (
				($comment['user_id'] == UGManager::_GUEST_ID  || $comment['user_id'] != $this->_session->getUserId())
				&& !$this->_acl->check( 'comments_manage' )
			) {
				throw new Module_NoPermission;
			}
			// Build form and validation
			$form = new View_form( 'edit.html', 'comments', false );
			$form->addElement( 'comments/id', $comment['id'], 'ID', new Validator_Int );
			$form->addElement( 'comments/website', $comment['website'], t('Website'), new Validator_Url(false), false );
			$form->addElement( 'comments/body', $comment['body'], t('Message'), new Validator_Length(10, 4096) );
			if ( $form->hasInput() && $form->isValid() ) {
				$fd = $form->getValues( 'comments' );
				unset( $fd['id'] );
				try {
					$commentId = $this->_model()->edit( $comment['id'], $fd );
					$this->_event->success( sprintf( t('Edited Comment #%1$d'), $comment['id'] ) );
					if ( $editInline === false ) {
						return zula_redirect( $this->_router->makeUrl( 'comments', 'config' ) );
					} else {
						if ( $this->_router->getSiteType() != $this->_router->getDefaultSiteType() ) {
							$comment['url'] = $this->_router->getSiteType().'/'.$comment['url'];
						}
						return zula_redirect( $this->_router->makeUrl( $comment['url'] )->fragment( 'comment-'.$comment['id'] ) );
					}
				} catch ( Exception $e ) {
					$this->_event->error( $e->getMessage() );
				}
			}
			$form->assign( array('edit_inline' => $editInline) );
			return $form->getOutput();
		}

	}

?>
