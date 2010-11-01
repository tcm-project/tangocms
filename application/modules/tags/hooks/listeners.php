<?php

/**
 * Zula Framework Module
 * Hooks file for listening to possible events
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Robert Clipsham
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009, 2010 Robert Clipsham
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Tags
 */

	class Tags_hooks extends Zula_HookBase {

		public function __construct() {
			parent::__construct( $this );
		}

		/**
		 * 'additional_form_content_table' Hook
		 *
		 * @param array $mcs
		 * @param string $formType
		 * @param string $contentUrl
		 * @return array|null
		 */
		public function hookAmcFormTable( $mcs, $formType, $contentUrl ) {
			$outputType = Module::getLoadingObj()->getOutputType();
			if (
				(Zula_ControllerBase::_OT_CONFIG & $outputType) && (Zula_ControllerBase::_OT_CONTENT & $outputType)
				&& $this->_acl->check( 'tags_'.$formType )
			) {
				$defaultVal = '';
				if ( $formType == 'edit' ) {
					$defaultVal = implode( ', ', $this->_model( 'tags', 'tags' )->getTags( $contentUrl ) );
				}
				return array(
						'onSuccess'	=> array($this, 'manageTags'),
						'inputs'	=> array(
											array(
												'name'		=> t('Tags', _PROJECT_ID.'-tags'),
												'desc'		=> t('Separate tags with commas.', _PROJECT_ID.'-tags'),
												'type'		=> 'input',
												'args'		=> array('content_tags', $defaultVal),
												'validators'=> array(
																	array($this, 'tagValidator')
																),
												'required'	=> false
												)
											)
						);
			}
			return null;
		}

		/**
		 * 'module_output_top' Hook
		 *
		 * @TODO Add a config controller to configure whether to use top/bottom
		 * @param array $mcs
		 * @param int $outputType
		 * @param string $sector
		 * @return string
		 */
		public function hookModuleOutputTop( $mcs, $outputType, $sector ) {
			if ( $sector == 'SC' ) {
				$url = $this->_router->getRequestPath( Router::_TRIM_ALL );
				if ( !$url ) {
					// Gather from the dispatch data instead (Bug #183)
					$dispatchData = $this->_dispatcher->getDispatchData();
					$siteType = $this->_router->getSiteType();
					if ( $siteType == $this->_router->getDefaultSiteType() ) {
						$url = '';
					} else {
						$url = $siteType.'/';
					}
					$url .= $dispatchData['module'].'/'.$dispatchData['controller'].'/'.$dispatchData['section'];
				}
				$tags = $this->_model( 'tags', 'tags' )->getTags( $url );
				if ( count( $tags ) > 0 ) {
					$view = new View( 'tags.html', 'tags' );
					$view->assign( array(
										'TAGS'			=> $tags,
										'MCS'			=> $url,
										'OUTPUT_TYPE' 	=> $outputType
										));
					return $view->getOutput();
				}
			}
		}

		/**
		 * Validator for tags
		 *
		 * @param string $tags
		 * @return bool
		 */
		public function tagValidator( $tags ) {
			$tags = array_map( 'trim', explode( ',', $tags ) );
			// Check the length of each tag is not too long
			$errors = array();
			foreach( $tags as $tag ) {
				if ( zula_strlen( $tag ) > 255 ) {
					$errors[] = sprintf( t('Tag "%s" is too long', _PROJECT_ID.'-tags'), $tag );
				}
			}
			return empty($errors) ? true : $errors;
		}

		/**
		 * Add/Edit tags for the content
		 *
		 * @param array $inputValues
		 * @param string $contentUrl
		 */
		public function manageTags( $inputValues, $contentUrl ) {
			if ( empty( $inputValues['amcForm']['content_tags'] ) ) {
				$this->_model( 'tags', 'tags' )->delUrlTags( $contentUrl );
			} else {
				try {
					$this->_model( 'tags', 'tags' )->setTags( $contentUrl, $inputValues['amcForm']['content_tags'] );
				} catch( Tag_TooLong $e ) {
					$this->_event->error( sprintf( t('Unable to manage tags, tag "%1$s" too long'), $e->getMessage() ) );
				}
			}
		}

		/**
		 * Hook: tags_display_modes
		 * Gets all display modes that this module has
		 *
		 * @return array
		 */
		public function hookTagsDisplayModes() {
			return array(
					'cloud'	=> t('Cloud', _PROJECT_ID.'-tags')
					);
		}

		/**
		 * Hook: tags_resolve_mode
		 * Resolves a given Controller, Section and config data to an
		 * available display mode offered.
		 *
		 * @param string $cntrlr
		 * @param string $sec
		 * @param array $config
		 * @return string
		 */
		public function hookTagsResolveMode( $cntrlr, $sec, $config ) {
			switch( $cntrlr ) {
				case 'cloud':
					return 'cloud';
				default:
					return 'index';
			}
		}
		/**
		 * Hook: tags_display_mode_config
		 * Returns HTML (commonly a table) to configure a display mode
		 *
		 * @param string $mode
		 * @return string
		 */
		public function hookTagsDisplayModeConfig( $mode ) {
			$view = new View( 'layout_edit/cloud.html', 'tags' );
			return $view->getOutput();
		}

	}
?>
