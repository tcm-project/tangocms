<?php

/**
 * Zula Framework Module (content_layout)
 * --- Edits/Configures details of a module attached to a sector
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Content_layout
 */

	class Content_layout_controller_edit extends Zula_ControllerBase {

		/**
		 * Constructor
		 *
		 * @return object
		 */
		public function __construct( $moduleDetails, $config, $sector ) {
			parent::__construct( $moduleDetails, $config, $sector );
			$this->setPageLinks( array(
										t('Manage Layouts')	=> $this->_router->makeUrl( 'content_layout' ),
										t('Add Layout')		=> $this->_router->makeUrl( 'content_layout', 'index', 'add' ),
										));
		}

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
		 * Allows the user to configure the module that is attached to a
		 * sector. This, however, currently depends on JavaScript enabled.
		 *
		 * @return string
		 */
		public function indexSection( $layoutName=null ) {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('Edit Module') );
			$this->setOutputType( self::_OT_CONFIG );
			// Check permission and if a layout has been provided
			if ( !$this->_acl->check( 'content_layout_config_module' ) ) {
				throw new Module_NoPermission;
			} else if ( $layoutName === null ) {
				$this->_event->error( t('Unable to edit attached module, no layout given') );
				return zula_redirect( $this->_router->makeUrl( 'content_layout' ) );
			}
			// Get correct cntrlr ID that is to be edited
			try {
				$cntrlrId = $this->_router->getArgument( 'id' );
			} catch ( Router_ArgNoExist $e ) {
				try {
					$cntrlrId = $this->_input->post( 'content_layout/cid' );
				} catch ( Input_KeyNoExist $e ) {
					$this->_event->error( t('Unable to edit attached module, no ID given') );
					return zula_redirect( $this->_router->makeUrl( 'content_layout' ) );
				}
			}
			// Create the correct layout and ensure cntrlr exists
			$siteType = substr( $layoutName, 0, strpos($layoutName, '-') );
			$layout = new Theme_Layout( $layoutName, Theme::getSiteTypeTheme( $siteType ) );
			try {
				$cntrlr = $layout->getControllerDetails( $cntrlrId );
				$module = new Module( $cntrlr['mod'] );
				$this->setTitle( sprintf( t('Configure Attached Module "%1$s"'), $module->title ) );
				if ( !isset( $cntrlr['config']['clDescription'] ) ) {
					$cntrlr['config']['clDescription'] = '';
				}
			} catch ( Theme_Layout_ControllerNoExist $e ) {
				$this->_event->error( sprintf( t('Unable to edit controller "%1$d" as it does not exist'), $cntrlrId ) );
				return zula_redirect( $this->_router->makeUrl( 'content_layout', 'manage', $layoutName ) );
			} catch ( Module_NoExist $e ) {
				$this->_event->error( sprintf( t('Unable to edit attached module "%1$s" as it does not exist'), $cntrlr['mod'] ) );
				return zula_redirect( $this->_router->makeUrl( 'content_layout', 'manage', $layoutName ) );
			}
			/**
			 * Prepare form validation
			 */
			$form = new View_form( 'edit/module.html', $this->getDetail('name'), false );
			$form->addElement( 'content_layout/config/displayTitle',
							   $cntrlr['config']['displayTitle'],
							   t('Display Title'),
							   new Validator_InArray( array('true', 'false', 'custom') ) # Yes, that is a quoted bool.
							);
			$form->addElement( 'content_layout/config/customTitle', $cntrlr['config']['customTitle'], t('Custom Title'), new Validator_Length(0, 255) );
			$form->addElement( 'content_layout/config/htmlWrapClass', $cntrlr['config']['htmlWrapClass'], t('HTML Class'), new Validator_Length(0, 500) );
			$form->addElement( 'content_layout/config/clDescription', $cntrlr['config']['clDescription'], t('Description'), new Validator_Length(0, 255) );
			$form->addElement( 'content_layout/cntrlr', null, t('Controller'), new Validator_Alphanumeric('_-.!+') );
			$form->addElement( 'content_layout/section', null, t('Section'), new Validator_Alphanumeric('_-.!+') );
			$form->addElement( 'content_layout/config', null, t('Config'), new Validator_Is('array'), false );
			if ( $form->hasInput() && $form->isValid() ) {
				$fd = $form->getValues( 'content_layout' );
				try {
					$layout->editController( $cntrlr['id'],
											 array(
												'con'	=> $fd['cntrlr'],
												'sec'	=> $fd['section'],
												'order'	=> $cntrlr['order'],
												'config'=> isset($fd['config']) ? $fd['config'] : array()
											 )
										   );
					try {
						$roles = $this->_input->post( 'acl_resources/layout_controller_'.$cntrlr['id'] );
					} catch ( Input_ValueNoExist $e ) {
						$roles = array();
					}
					$this->_acl->allowOnly( 'layout_controller_'.$cntrlr['id'], $roles );
					if ( $layout->save() ) {
						$this->_event->success( sprintf( t('Configured attached module ID "%d"'), $cntrlr['id'] ) );
					} else {
						$this->_event->error( t('Unable to save layout, ensure file is writable') );
					}
				} catch ( Theme_Layout_ControllerNoExist $e ) {
					$this->_event->error( sprintf( t('Unable to edit attached module ID "%d" as it does not exist'), $cntrlr['id'] ) );
				} catch ( Theme_SectorMapNotWriteable $e ) {
					$this->_event->error( sprintf( t('Unable to edit module in sector map: $s'), $e->getMessage() ) );
				}
				return zula_redirect( $this->_router->makeUrl( 'content_layout', 'manage', $layoutName ) );
			}
			/**
			 * Gets all displays modes that this module offers, the current display
			 * mode being used - once done start building up the form.
			 */
			$displayModes = Hooks::notifyAll( $module->name.'_display_modes' );
			$currentMode = Hooks::notifyAll( $module->name.'_resolve_mode', $cntrlr['con'], $cntrlr['sec'], $cntrlr['config'] );
			$this->addAsset( 'js/edit.js' );
			$form->assign( array(
								'CID'			=> $cntrlr['id'],
								'LAYOUT_NAME'	=> $layoutName,
								'MODULE'		=> $module->getDetails(),
								'DISPLAY_MODES'	=> empty($displayModes) ? array('default' => t('Default')) : $displayModes,
								'CURRENT_MODE'	=> empty($currentMode) ? 'default' : $currentMode[0],
								));
			$jsConfig = array_merge( $cntrlr, $cntrlr['config'] );
			unset( $jsConfig['config'] );
			$form->assignHtml( array(
									'JS_CONFIG'	=> zula_array_js_string( $jsConfig ),
									'ACL_FORM'	=> $this->_acl->buildForm( array(t('View attached module') => 'layout_controller_'.$cntrlr['id']) ),
									));
			return $form->getOutput();
		}

		/**
		 * Gets the display mode configuration details from the hooks
		 * of the correct module. This is AJAX only.
		 *
		 * @return string
		 */
		public function modeConfigSection() {
			if ( !_AJAX_REQUEST ) {
				throw new Module_AjaxOnly;
			}
			$hook = $this->_router->getArgument( 'module' ).'_display_mode_config';
			$data = Hooks::notifyAll( $hook, $this->_router->getArgument('mode') );
			return $data ? implode( "\n", $data ) : false;
		}

	}

?>
