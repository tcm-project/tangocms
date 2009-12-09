<?php
// $Id: post.php 2768 2009-11-13 18:12:34Z alexc $
/*
 * Post a new comment for a given request path
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Comments
 */

	class Comments_controller_post extends Zula_ControllerBase {

		/**
		 * Posts a new comment for a given request path, if it
		 * is valid. Once done it will return back to the URL
		 *
		 * @return bool|string
		 */
		public function indexSection() {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('Post Comment') );
			if ( !$this->_acl->check( 'comments_post' ) ) {
				throw new Module_NoPermission;
			}
			// Check request path hash
			try {
				$hashPath = $this->_input->post( 'comments/hash' );
				if ( isset( $_SESSION['comments'][ $hashPath ] ) ) {
					$requestPath = $_SESSION['comments'][ $hashPath ]['path'];
					$siteType = $_SESSION['comments'][ $hashPath ]['siteType'];
				} else {
					throw new Exception;
				}
			} catch ( Exception $e ) {
				if ( empty( $_SESSION['previous_url'] ) ) {
					$_SESSION['previous_url'] = $this->_router->getBaseUrl();
				}
				$this->_event->error( t('Unable to post comment') );
				return zula_redirect( $_SESSION['previous_url'] );
			}
			// Build and validate form
			$isGuest = $this->_session->isLoggedIn() == false;
			$form = new View_form( 'form.html', 'comments' );
			$form->antispam( true );
			$form->addElement( 'comments/hash', $hashPath, 'hash path', new Validator_Alphanumeric );
			$form->addElement( 'comments/name', null, t('Name'), new Validator_Length(0,255), $isGuest );
			$form->addElement( 'comments/website', null, t('Website'), new Validator_Url(false), false );
			$form->addElement( 'comments/body', null, t('Message'), new Validator_Length(10, 4096) );
			if ( $form->hasInput() && $form->isValid() ) {
				$fd = $form->getValues( 'comments' );
				if ( $isGuest == false ) {
					$fd['name'] = ''; # Registered users will have their own details used
				}
				try {
					$commentId = $this->_model()->add( $requestPath, $fd['body'], $fd['name'], $fd['website'] );
					// Redirect back to correct place
					if ( $siteType != $this->_router->getDefaultSiteType() ) {
						$requestPath = $siteType.'/'.$requestPath;
					}
					$url = $this->_router->makeUrl( $requestPath );
					if ( $this->_config->get( 'comments/moderate' ) ) {
						$this->_event->success( t('Comment is now in the moderation queue') );
					} else {
						$this->_event->success( t('Added new comment') );
						$url->fragment( 'comment-'.$commentId );
					}
					return zula_redirect( $url );
				} catch ( Exception $e ) {
					$this->_event->error( $e->getMessage() );
				}
			}
			$this->_session->storePrevious( false ); # Don't store this URL as previous
			return $form->getOutput();
		}

	}

?>
