<?php

/**
 * Zula Framework Module
 * Hooks file for listening to possible events
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Aliases
 */

	class Aliases_hooks extends Zula_HookBase {

		/**
		 * Current loaded aliases that will be used to customize the URL structure
		 * @var array
		 */
		protected $aliases = array();

		/**
		 * Constructor
		 * Calls the parent constructor to register the methods
		 *
		 * @return object
		 */
		public function __construct() {
			parent::__construct( $this );
			if ( !$this->aliases = $this->_cache->get( 'aliases' ) ) {
				$query = 'SELECT id, alias, url, redirect
						  FROM {PREFIX}mod_aliases ORDER BY alias ASC';
				foreach( $this->_sql->query( $query, PDO::FETCH_ASSOC ) as $row ) {
					$this->aliases[ trim($row['alias'], '/') ] = array(
																	'id'		=> $row['id'],
																	'alias'		=> $row['alias'],
																	'url' 		=> trim($row['url'], '/'),
																	'redirect'	=> (bool) $row['redirect']
																	);
				}
				$this->_cache->add( 'aliases', $this->aliases );
			}
			if ( !is_array( $this->aliases ) ) {
				$this->aliases = array();
			}
		}

		/**
		 * hook: 'cntrlr_error_output'
		 * Provides 'Wiki like' functionaility for the Page module
		 *
		 * @param int $statusCode
		 * @param string $output
		 * @return string
		 */
		public function hookCntrlrErrorOutput( $statusCode, $output ) {
			if (
				$statusCode == 404 && Module::exists( 'page' ) && !Module::isDisabled( 'page' ) &&
				$this->_acl->checkMulti( array('page_manage', 'aliases_add'), ACL::_MULTI_ALL )
			) {
				$alias = $this->_router->getRequestPath();
				if ( trim( $alias ) ) {
					$msgStr = t('This page can be <a href="%1$s">created</a> with an alias of <strong>"%2$s"</strong>, simply <a href="%1$s">add this page</a> now.', _PROJECT_ID.'-page');
					$output .= '<h3>'.t('Create this Page', _PROJECT_ID.'-page').'</h3>';
					$output .= sprintf( '<p>'.$msgStr.'</p>',
										$this->_router->makeUrl( 'page', 'config', 'add', null, array('qe' => 'true', 'alias' => base64_encode($alias)) ),
										zula_htmlspecialchars( $alias )
									  );
				}
			}
			return $output;
		}

		/**
		 * 'amc_form_table' Hook
		 *
		 * @param array $mcs
		 * @param string $formType
		 * @param string $contentUrl
		 * @return array|null
		 */
		public function hookAmcFormTable( $mcs, $formType, $contentUrl ) {
			$outputType = Module::getLoadingObj()->getOutputType();
			if (
				(Zula_ControllerBase::_OT_CONFIG & $outputType) && (Zula_ControllerBase::_OT_CONTENT_STATIC & $outputType)
				&& $this->_acl->check( 'aliases_'.$formType )
			) {
				try {
					$defaultVal = base64_decode( $this->_router->getArgument( 'alias' ) );
				} catch ( Router_ArgNoExist $e ) {
					try {
						$alias = $this->_model( 'aliases', 'aliases' )->getDetails( $contentUrl, Aliases_Model::_URL );
						$defaultVal = $alias['alias'];
					} catch ( Alias_NoExist $e ) {
						$defaultVal = '';
					}
				}
				$elements = array(
								'onSuccess'	=> array($this, $formType.'Alias'),
								'inputs'	=> array(
													array(
														'name'			=> t('URL alias', _PROJECT_ID.'-aliases'),
														'desc'			=> t('URL alias to be created for this page', _PROJECT_ID.'-aliases'),
														'type'			=> 'input',
														'args'			=> array('aliases_name', $defaultVal),
														'validators'	=> array(
																				new Validator_Length(1, 255),
																				new Validator_Alphanumeric('_-.!/')
																				),
														'required'		=> false,
														)
													)
								);
				return $elements;
			}
			return null;
		}

		/**
		 * Callback function to add an alias
		 *
		 * @param array $inputValues
		 * @param string $contentUrl
		 * @return void
		 */
		public function addAlias( $inputValues, $contentUrl ) {
			if ( !empty( $inputValues['amcForm']['aliases_name'] ) ) {
				$alias = $inputValues['amcForm']['aliases_name'];
				try {
					$this->_model( 'aliases', 'aliases' )->add( $alias, $contentUrl );
				} catch ( Alias_AlreadyExists $e ) {
					$this->_event->error( sprintf(
												t('URL alias "%1$s" already exists', _PROJECT_ID.'-aliases'),
												$alias
												)
										);
				}
			}
		}

		/**
		 * Callback function to edit an alias. If the provided
		 * alias is empty, it will be removed instead.
		 *
		 * @param array $inputValues
		 * @param string $contentUrl
		 */
		public function editAlias( $inputValues, $contentUrl ) {
			if ( isset( $inputValues['amcForm']['aliases_name'] ) ) {
				$newAlias = $inputValues['amcForm']['aliases_name'];
				try {
					$alias = $this->_model( 'aliases', 'aliases' )->getDetails( $contentUrl, Aliases_Model::_URL );
					if ( empty( $newAlias ) ) {
						$this->_model( 'aliases', 'aliases' )->delete( $alias['id'] );
					} else {
						$this->_model( 'aliases', 'aliases' )->edit( $alias['id'], $newAlias, $contentUrl, $alias['redirect'] );
					}
				} catch ( Alias_NoExist $e ) {
					$this->addAlias( $inputValues, $contentUrl );
				}
			}
		}

		/**
		 * 'router_make_url' Hook
		 *
		 * @param string $url
		 * @return string
		 */
		public function hookRouterMakeUrl( $url ) {
			$alias = trim( $url, '/ ' );
			$aliases = array();
			foreach( $this->aliases as $tmpAlias ) {
				$aliases[ $tmpAlias['url'] ] = $tmpAlias['alias'];
			}
			do {
				$break = false;
				if ( isset( $aliases[ $alias ] ) ) {
					$alias = $aliases[ $alias ];
				} else {
					$break = true;
				}
			} while( $break === false && $url !== $alias );
			return $alias;
		}

		/**
		 * 'router_pre_parse' Hook
		 *
		 * @param string $url
		 * @return string
		 */
		public function hookRouterPreParse( $url ) {
			$resolvedUrl = trim( $url, '/ ' );
			do {
				$break = false;
				if ( isset( $this->aliases[ $resolvedUrl ] ) ) {
					$redirect = $this->aliases[ $resolvedUrl ]['redirect'];
					$resolvedUrl = $this->aliases[ $resolvedUrl ]['url'];
				} else {
					$break = true;
				}
			} while( $break === false && $url !== $resolvedUrl );
			if ( !empty( $redirect ) && $url != $resolvedUrl ) {
				if ( zula_url_has_scheme( $resolvedUrl ) ) {
					$url = $resolvedUrl;
				} else {
					// Don't use makeFullUrl since that will re-alias this URL!
					$url = $this->_router->getBaseUrl();
					if ( $this->_router->getType() == 'standard' ) {
						$url .= 'index.php?url=';
					}
					$url .= $resolvedUrl;
				}
				zula_redirect( $url );
				die; # Eww, but needed.
			}
			return $resolvedUrl;
		}

		public function hookAliasesAdd( $alias, $url, $redirect ) {
			$this->aliases[ $alias ] = array(
											'alias'		=> $alias,
											'url'		=> $url,
											'redirect'	=> (bool) $redirect,
											);
			return true;
		}

		public function hookAliasesEdit( $id, $alias, $url, $redirect ) {
			$this->aliases[ $alias ] = array(
											'id'	=> $id,
											'alias'	=> $alias,
											'url'	=> $url,
											'redirect' => (bool) $redirect,
											);
			return true;
		}

		public function hookAliasesDelete( $id ) {
			foreach( $this->aliases as $key=>$tmpAlias ) {
				if ( $tmpAlias['id'] == $id ) {
					unset( $this->aliases[ $key ] );
					break;
				}
			}
			return true;
		}

	}

?>
