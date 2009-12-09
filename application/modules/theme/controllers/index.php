<?php
// $Id: index.php 2798 2009-11-24 12:15:41Z alexc $

/**
 * Zula Framework Module (Theme)
 * --- Change and view themes for the differnt site types
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
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
										t('Manage Themes')	=> $this->_router->makeUrl( 'theme' ),
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
			$this->setTitle( t('Manage Themes') );
			$this->_locale->textDomain( $this->textDomain() );
			$this->setOutputType( self::_OT_CONFIG );
			/**
			 * Gather all site types and get which theme it is currently using
			 */
			$siteTypes = array();
			foreach( $this->_router->getSiteTypes() as $siteType ) {
				try {
					$theme = $this->_config->get( 'theme/'.$siteType.'_default' );
				} catch ( Config_KeyNoExist $e ) {
					$theme = '';
				}
				$siteTypes[] = array(
									'site' 	=> $siteType,
									'theme' => $theme,
									'name' 	=> ucfirst( strtolower( $siteType ) )
									);
			}
			$view = $this->loadView( 'overview.html' );
			$view->assign( array(
								'THEMES' 			=> Theme::getAll(),
								'SITE_TYPES'		=> $siteTypes,
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
				$this->_locale->textDomain( $this->textDomain() );
				foreach( $this->_input->post( 'theme' ) as $siteType=>$theme ) {
					if ( Theme::exists( $theme ) ) {
						$this->_config_sql->update( 'theme/'.$siteType.'_default', $theme );
					} else {
						$this->_log->message( 'site type "'.$siteType.'" does not exist, unable to update', Log::L_WARNING );
					}
				}
				$this->_event->success( t('Updated themes') );
			} else {
				$this->_event->error( Input::csrfMsg() );
			}
			return zula_redirect( $this->_router->makeUrl( 'theme' ) );
		}

		/**
		 * Deletes the specified themes selected
		 *
		 * @return string
		 */
		public function deleteSection() {
			$this->setOutputType( self::_OT_CONFIG );
			if ( !$this->_acl->check( 'theme_delete' ) ) {
				throw new Module_NoPermission;
			} else if ( $this->_input->checkToken() ) {
				$this->_locale->textDomain( $this->textDomain() );
				$this->setTitle( t('Delete Theme') );
				$delCount = 0;
				try {
					foreach( $this->_input->post( 'themes' ) as $theme ) {
						try {
							$themeObj = new Theme( $theme );
							$themeObj->delete();
							++$delCount;
						} catch ( Theme_UnableToDelete $e ) {
							if ( $e->getCode() === 1 ) {
								$msg = t('You can not delete the last remaining theme');
							} else if ( $e->getCode() === 2 ) {
								$msg = t('Theme directory could not be deleted, please check permissions');
							} else {
								$msg = $e->getMesssage();
							}
							$this->_event->error( $msg );
						} catch ( Theme_NoExist $e ) {
						}
					}
					if ( $delCount > 0 ) {
						$this->_event->success( t('Deleted selected themes') );
					}
				} catch ( Input_KeyNoExist $e ) {
					$this->_event->error( t('No themes selected') );
				}
			} else {
				$this->_event->error( Input::csrfMsg() );
			}
			return zula_redirect( $this->_router->makeUrl( 'theme' ) );
		}

		/**
		 * Change settings regarding themeing and style
		 *
		 * @return string
		 */
		public function settingsSection() {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('Theme Settings') );
			$this->setOutputType( self::_OT_CONFIG );
			// Prepare form validation
			$form = new View_Form( 'settings.html', 'theme' );
			$form->addElement( 'theme/allow_user_override', $this->_config->get('theme/allow_user_override'), t('Allow User Override'), new Validator_Bool );
			if ( $form->hasInput() && $form->isValid() ) {
				$allowOverride = $form->getValues( 'theme/allow_user_override' );
				try {
					$this->_config_sql->update( 'theme/allow_user_override', $allowOverride );
				} catch ( Config_KeyNoExist $e ) {
					$this->_config_sql->add( 'theme/allow_user_override', $allowOverride );
				}
				$this->_event->success( t('Updated Theme Settings') );
				return zula_redirect( $this->_router->makeUrl( 'theme', 'index', 'settings' ) );
			}
			return $form->getOutput();
		}

	}

?>
