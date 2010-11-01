<?php

/**
 * Zula Framework Module
 * Change and view themes for the differnt site types
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Theme
 */

	class Theme_controller_index extends Zula_ControllerBase {

		/**
		 * Constructor
		 *
		 * @return object
		 */
		public function __construct( $moduleDetails, $config, $sector ) {
			parent::__construct( $moduleDetails, $config, $sector );
			$this->setPageLinks( array(
										t('Manage themes')	=> $this->_router->makeUrl( 'theme' ),
										t('Settings')		=> $this->_router->makeUrl( 'theme', 'index', 'settings' ),
										));
		}

		/**
		 * Lists all of the themes and allows the user to change
		 * the theme for the different site types.
		 *
		 * @return strng
		 */
		public function indexSection() {
			$this->setOutputType( self::_OT_CONFIG );
			// Check which site type to change theme for
			try {
				$siteType = strtolower( $this->_input->get('type') );
			} catch ( Input_KeyNoExist $e ) {
				$siteType = $this->_router->getDefaultSiteType();
			}
			if ( !$this->_router->siteTypeExists( $siteType ) ) {
				$this->_event->error( t('Selected site type does not exist') );
				$siteType = $this->_router->getDefaultSiteType();
			}
			$this->setTitle( sprintf( t('"%s" theme & style'), ucfirst($siteType) ), false );
			$view = $this->loadView( 'overview.html' );
			$view->assign( array(
								'THEMES' 	=> Theme::getAll(),
								'CURRENT'	=> $this->_config->get( 'theme/'.$siteType.'_default' ),
								'SITE_TYPE'	=> $siteType,
								));
			$view->assignHtml( array('CSRF' => $this->_input->createToken(true)) );
			return $view->getOutput();
		}

		/**
		 * Updates which theme should be used for the different
		 * site types.
		 *
		 * @return string
		 */
		public function updateSection() {
			if ( !$this->_acl->check( 'theme_update' ) ) {
				throw new Module_NoPermission;
			} else if ( $this->_input->checkToken() ) {
				try {
					$siteType = $this->_input->post( 'theme_site_type' );
					if ( $this->_router->siteTypeExists( $siteType ) ) {
						$theme = $this->_input->post( 'theme' );
						if ( Theme::exists( $theme ) ) {
							$this->_config_sql->update( 'theme/'.$siteType.'_default', $theme );
							$this->_event->success( t('Updated default theme') );
						}
					} else {
						$this->_event->error( t('Selected site type does not exist') );
						$siteType = null;
					}
				} catch ( Input_KeyNoExist $e ) {
					$this->_event->error( t('Please select a theme to use as the default') );
				}
			} else {
				$this->_event->error( Input::csrfMsg() );
			}
			$siteType = isset($siteType) ? $siteType : $this->_router->getDefaultSiteType();
			return zula_redirect( $this->_router->makeUrl('theme')->queryArgs( array('type' => $siteType) ) );
		}

		/**
		 * Change settings regarding themeing and style
		 *
		 * @return string
		 */
		public function settingsSection() {
			$this->setTitle( t('Theme settings') );
			$this->setOutputType( self::_OT_CONFIG );
			// Prepare form validation
			$form = new View_Form( 'settings.html', 'theme' );
			$form->addElement( 'theme/allow_user_override', $this->_config->get('theme/allow_user_override'), t('Allow user override'), new Validator_Bool );
			if ( $form->hasInput() && $form->isValid() ) {
				$allowOverride = $form->getValues( 'theme/allow_user_override' );
				try {
					$this->_config_sql->update( 'theme/allow_user_override', $allowOverride );
				} catch ( Config_KeyNoExist $e ) {
					$this->_config_sql->add( 'theme/allow_user_override', $allowOverride );
				}
				$this->_event->success( t('Updated theme settings') );
				return zula_redirect( $this->_router->makeUrl( 'theme', 'index', 'settings' ) );
			}
			return $form->getOutput();
		}

	}

?>
