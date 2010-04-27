<?php

/**
 * Zula Framework Module (content_layout)
 * --- Shows all of the template sectors and which modules are current
 * attached to it
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Content_layout
 */

	class Content_layout_controller_index extends Zula_ControllerBase {

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
		 * Displays all layouts for a site type
		 *
		 * @param string $siteType
		 * @return string
		 */
		public function indexSection( $siteType=null ) {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setOutputType( self::_OT_CONFIG );
			// Check if there is a specified site type to manage layout for
			if ( empty( $siteType ) ) {
				try {
					$siteType = $this->_input->get( 'type' );
				} catch ( Input_KeyNoExist $e ) {
					$siteType = $this->_router->getDefaultSiteType();
				}
			}
			$siteType = strtolower( $siteType );
			if ( !$this->_router->siteTypeExists( $siteType ) ) {
				$this->_event->error( t('Selected site type does not exist') );
				return zula_redirect( $this->_router->makeUrl( 'content_layout' ) );
			}
			$this->setTitle( sprintf( t('"%s" Content Layouts'), ucfirst( $siteType ) ), false );

			// Find out what module is being used in the fpsc layout
			$fpsc = new Layout( 'fpsc-'.$siteType );
			$cntrlrs = $fpsc->getControllers( 'SC' );
			$fpscCntrlr = reset( $cntrlrs );

			// Get all modules for the user to choose from, with title
			$modules = array();
			foreach( Module::getModules() as $mod ) {
				$modObj = new Module( $mod );
				$modules[ $modObj->name ] = $modObj->title;
			}

			// Gather all layouts and build view
			$view = $this->loadView( 'index/main.html' );
			$view->assign( array(
								'SITE_TYPE'		=> $siteType,
								'LAYOUTS'		=> Layout::getAll( $siteType ),
								'FPSC_MODULE'	=> $fpscCntrlr['mod'],
								'MODULES'		=> $modules,
								));
			$view->assignHtml( array( 'CSRF' => $this->_input->createToken( true ) ) );
			return $view->getOutput();
		}

		/**
		 * Provides ability to add a new content layout. The user will
		 * be redirect to the page, as if they had gone 'Edit' on the
		 * layout once it has been created.
		 *
		 * @return string
		 */
		public function addSection() {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('Add New Layout') );
			$this->setOutputType( self::_OT_CONFIG );
			try {
				$cloner = $this->_router->getArgument( 'clone' );
				$cloner = new Layout( $cloner );
				if ( $cloner->exists() ) {
					$cloneName = $cloner->getName();
					$this->setTitle( sprintf( t('Clone Layout "%1$s"'), $cloneName ) );
				} else {
					throw new Exception;
				}
			} catch ( Exception $e ) {
				$cloneName = null;
				$cloneRegex = null;
			}
			// Build and check form
			$form = new View_Form( 'index/form_layout.html', 'content_layout' );
			$form->action( $this->_router->makeUrl( 'content_layout', 'index', 'add' ) );
			$form->addElement( 'content_layout/name', null, t('Name'), array(new Validator_Alphanumeric('-'), new Validator_Length(2, 225)) );
			$form->addElement( 'content_layout/regex', $cloneRegex, t('URL/Regex'), new Validator_Length(2, 255) );
			$form->addElement( 'content_layout/site_type', $this->_router->getDefaultSiteType(), t('Site Type'),
								new Validator_InArray( $this->_router->getSiteTypes() )
							 );
			$form->addElement( 'content_layout/clone', $cloneName, t('Clone'), array(new Validator_Alphanumeric('-'), new Validator_Length(0, 225)) );
			if ( $form->hasInput() && $form->isValid() ) {
				$fd = $form->getValues( 'content_layout' );
				// Check if we are cloning a layout
				if ( $fd['clone'] ) {
					$layout = new Layout( $fd['clone'] );
					$layout->setName( $fd['site_type'].'-'.$fd['name'] );
				} else {
					$layout = new Layout( $fd['site_type'].'-'.$fd['name'] );
				}
				$layout->setRegex( $fd['regex'] );
				$path = $this->_zula->getDir('config').'/layouts/'.$layout->getName().'.xml';
				if ( $layout->save( $path ) ) {
					$this->_event->success( t('Added new content layout') );
					return zula_redirect( $this->_router->makeUrl( 'content_layout', 'manage', $layout->getName() ) );
				}
				$this->_event->error( t('Unable to save content layout') );
			}
			return $form->getOutput();
		}

		/**
		 * Deletes multiple content layouts
		 *
		 * @return bool
		 */
		public function deleteSection() {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('Delete Layouts') );
			$this->setOutputType( self::_OT_CONFIG );
			if ( $this->_input->checkToken() ) {
				try {
					$delCount = 0;
					foreach( $this->_input->post( 'layout_names' ) as $layoutName ) {
						$layout = new Layout( $layoutName );
						if ( $layout->delete() ) {
							$delCount++;
						} else {
							$this->_event->error( sprintf( t('Unable to delete layout "%1$s"'), $layoutName ) );
						}
					}
					if ( $delCount > 0 ) {
						$this->_event->success( t('Deleted selected layouts') );
					}
				} catch ( Input_KeyNoExist $e ) {
					$this->_event->error( t('No layouts selected') );
				}
			} else {
				$this->_event->error( Input::csrfMsg() );
			}
			return zula_redirect( $this->_router->makeUrl( 'content_layout' ) );
		}

	}

?>
