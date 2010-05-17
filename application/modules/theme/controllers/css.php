<?php

/**
 * Zula Framework Module (Theme)
 * --- Allows a user to edit the CSS files for the theme
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Theme
 */

	class Theme_controller_css extends Zula_ControllerBase {

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
		 * Allows for shorter URLs. Selects a CSS file to edit, will use
		 * the first CSS file if none is specified
		 *
		 * @param string $name
		 * @param array $args
		 * @return string|bool
		 */
		public function __call( $name, $args ) {
			if ( !$this->_acl->check( 'theme_view_css' ) ) {
				throw new Module_NoPermission;
			}
			$this->_i18n->textDomain( $this->textDomain() );
			$this->setTitle( t('Edit CSS Files') );
			$this->setOutputType( self::_OT_CONFIG );
			/**
			 * Gather details of the theme and check if the CSS file exists
			 */
			try {
				$theme = new Theme( substr($name, 0, -7) );
				$this->setTitle( sprintf( t('"%s" Stylesheets'), $theme->getDetail( 'title' ) ) );
				// All CSS files for this theme
				$themeCssFiles = $theme->getAllCss();
				if ( empty( $themeCssFiles ) ) {
					$this->_event->error( t('There are no CSS files available') );
					return zula_redirect( $this->_router->makeUrl( 'theme' ) );
				} else {
					try {
						$selectedCss = $this->_router->getArgument( 'file' );
					} catch ( Router_ArgNoExist $e ) {
						$cssDetails = reset( $themeCssFiles );
						$selectedCss = $cssDetails['name'];						
					}
					if ( isset( $themeCssFiles[ $selectedCss ] ) ) {
						$cssDetails = $themeCssFiles[ $selectedCss ];
					} else {
						// Eek, the CSS file does not exist!
						$this->_event->error( sprintf( t('Stylesheet "%1$s" does not exist'), $selectedCss ) );
						return zula_redirect( $this->_router->makeUrl( 'theme', 'css', $theme->getDetail('name') ) );
					}
				}
			} catch ( Theme_NoExist $e ) {
				$this->_event->error( t('Theme does not exist') );
				return zula_redirect( $this->_router->makeUrl( 'theme' ) );
			}
			/**
			 * Setup the form and validation for the contents
			 */
			$form = new View_form( 'css.html', 'theme' );
			$form->addElement( 'theme/body', file_get_contents($cssDetails['path']), t('Stylesheet'), new Validator_length(0, 10000) );
			if ( $form->hasInput() && $form->isValid() ) {
				if ( !$this->_acl->check( 'theme_edit_css' ) ) {
					throw new Module_NoPermission;
				} else if ( zula_is_writable( $cssDetails['path'] ) ) {
					file_put_contents( $cssDetails['path'], $form->getValues('theme/body') );
					$this->_event->success( t('Stylesheet updated') );
					return zula_redirect( $this->_router->makeUrl( 'theme', 'css', $theme->getDetail('name'), null, array('file' => $cssDetails['name']) ) );
				} else {
					$this->_event->error( sprintf( t('CSS file "%s" is not writable'), $cssDetails['path'] ) );
				}
			}
			// Assign other data to be provided to the view file
			$form->assign( array(
								'CSS_FILES'	=> $themeCssFiles,
								'CSS_NAME'	=> $selectedCss,
								'THEME'		=> array('NAME' => $theme->getDetail('name')),
								));
			return $form->getOutput();
		}

	}

?>
