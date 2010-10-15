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
 * @package Zula_Installer
 */

	class Stage_controller_five extends Zula_ControllerBase {

		/**
		 * Update common basic settings so a user doesn't forget
		 * to change them after installation.
		 *
		 * @return bool|string
		 */
		public function indexSection() {
			$this->setTitle( t('Basic configuration') );
			/**
			 * Make sure user is not skipping ahead
			 */
			if ( !isset( $_SESSION['install_stage'] ) || $_SESSION['install_stage'] !== 5 ) {
				return zula_redirect( $this->_router->makeUrl('stage', 'one') );
			}
			$form = new View_form( 'stage5/settings.html', 'stage' );
			$form->addElement( 'settings/config/title', null, t('Site title'), new Validator_Length(0, 255) );
			$form->addElement( 'settings/meta/description', null, t('Meta description'), new Validator_Length(0, 255) );
			$form->addElement( 'settings/mail/outgoing', null, t('Outgoing email'), new Validator_Email );
			$form->addElement( 'settings/mail/incoming', null, t('Incoming email'), new Validator_Email );
			$form->addElement( 'settings/mail/subject_prefix', true, t('Email prefix'), new Validator_Bool );
			if ( $form->hasInput() && $form->isValid() ) {
				$fd = $form->getValues();
				foreach( $fd['settings'] as $confCat=>$val ) {
					foreach( $val as $confKey=>$value ) {
						$this->_config_sql->update( $confCat.'/'.$confKey, $value );
					}
				}
				// Update scheme/protocol that is being used
				$this->_config_sql->add( 'config/protocol', $this->_router->getScheme() );
				$_SESSION['install_stage']++;
				return zula_redirect( $this->_router->makeUrl('stage', 'six') );
			}
			return $form->getOutput();
		}

	}

?>
