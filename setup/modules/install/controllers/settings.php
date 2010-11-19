<?php

/**
 * Zula Framework Module
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Evangelos Foutras
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Setup
 */

	class Install_controller_settings extends Zula_ControllerBase {

		/**
		 * Update common basic settings so a user doesn't forget
		 * to change them after installation.
		 *
		 * @return bool|string
		 */
		public function indexSection() {
			$this->setTitle( t('Basic configuration') );
			if (
				$this->_zula->getMode() != 'cli' &&
				(!isset( $_SESSION['installStage'] ) || $_SESSION['installStage'] !== 5)
			) {
				return zula_redirect( $this->_router->makeUrl('install', 'checks') );
			}
			// Get data from either a form or CLI arguments
			if ( $this->_zula->getMode() == 'cli' ) {
				$title = $this->_input->cli( 't' );
				$email = $this->_input->cli( 'e' );
				$data = array(
							'config'=> array('title' => $title),
							'mail'	=> array(
											'outgoing'	=> $email,
											'incoming'	=> $email,
											));
			} else {
				$form = new View_form( 'settings.html', 'install' );
				$form->addElement( 'settings/config/title', null, t('Site title'), new Validator_Length(0, 255) );
				$form->addElement( 'settings/meta/description', null, t('Meta description'), new Validator_Length(0, 255) );
				$form->addElement( 'settings/mail/outgoing', null, t('Outgoing email'), new Validator_Email );
				$form->addElement( 'settings/mail/incoming', null, t('Incoming email'), new Validator_Email );
				$form->addElement( 'settings/mail/subject_prefix', true, t('Email prefix'), new Validator_Bool );
				if ( $form->hasInput() && $form->isValid() ) {
					$data = $form->getValues( 'settings' );
				} else {
					return $form->getOutput();
				}
			}
			foreach( $data as $confRealm=>$confValues ) {
				foreach( $confValues as $key=>$val ) {
					$this->_config_sql->update( $confRealm.'/'.$key, $val );
				}
			}
			// Update scheme/protocol that is being used
			$this->_config_sql->add( 'config/protocol', $this->_router->getScheme() );
			if ( isset( $_SESSION['installStage'] ) ) {
				++$_SESSION['installStage'];
			}
			$this->_event->success( t('Basic configuration updated') );
			return zula_redirect( $this->_router->makeUrl('install', 'complete') );
		}

	}

?>
