<?php

/**
 * Zula Framework Module (Aliases)
 * --- Configure URL Aliases that are used with the Zula Routers
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Aliases
 */

	class Aliases_controller_index extends Zula_ControllerBase {

		/**
		 * Amount of  aliases to show per-page with Pagination
		 */
		const _PER_PAGE = 12;

		/**
		 * Constructor
		 *
		 * Sets the common page links
		 */
		public function __construct( $moduleDetails, $config, $sector ) {
			parent::__construct( $moduleDetails, $config, $sector );
			$this->setPageLinks( array(
										t('Manage Aliases')	=> $this->_router->makeUrl( 'aliases' ),
										t('Add Alias')		=> $this->_router->makeUrl( 'aliases', 'index', 'add' ),
										));
		}

		/**
		 * Displays URL Aliases with Pagination, also allows the
		 * user to easily add/edit/remove existing aliases
		 *
		 * @return string
		 */
		public function indexSection() {
			if ( !$this->_acl->checkMulti( array('aliases_add', 'aliases_edit', 'aliases_delete') ) ) {
				throw new Module_NoPermission;
			}
			$this->_i18n->textDomain( $this->textDomain() );
			$this->setTitle( t('URL Aliases') );
			$this->setOutputType( self::_OT_CONFIG );
			// Get all the needed aliases to display
			if ( $this->inSector( 'SC' ) && $this->_input->has( 'get', 'page' ) ) {
				$curPage = abs( $this->_input->get( 'page' )-1 );
			} else {
				$curPage = 0;
			}
			$aliases = $this->_model()->getAll( self::_PER_PAGE, self::_PER_PAGE*$curPage );
			$aliasCount = $this->_model()->getCount();
			if ( $aliasCount > 0 ) {
				$pagination = new Pagination( $aliasCount, self::_PER_PAGE );
   			}
			// Build and display view
			$view = $this->loadView( 'overview.html' );
			$view->assign( array('ALIASES' => $aliases) );
			$view->assignHtml( array(
									'PAGINATION'	=> isset($pagination) ? $pagination->build() : null,
									'CSRF'			=> $this->_input->createToken( true ),
									));
			// Autocomplete/suggest feature
			$this->_theme->addJsFile( 'jquery.autocomplete' );
			$this->_theme->addCssFile( 'jquery.autocomplete.css' );
			$this->addAsset( 'js/autocomplete.js' );
			return $view->getOutput();
		}

		/**
		 * Autocomplete/autosuggest JSON response
		 *
		 * @return false
		 */
		public function autocompleteSection() {
			try {
				$query = $this->_input->get( 'query' );
				$searchTitle = '%'.str_replace( '%', '\%', $query ).'%';
				$pdoSt = $this->_sql->prepare( 'SELECT id, alias FROM {SQL_PREFIX}mod_aliases WHERE alias LIKE ?' );
				$pdoSt->execute( array($searchTitle) );
				// Setup the object to return
				$jsonObj = new StdClass;
				$jsonObj->query = $query;
				foreach( $pdoSt->fetchAll( PDO::FETCH_ASSOC ) as $row ) {
					$jsonObj->suggestions[] = $row['alias'];
					$jsonObj->data[] = $this->_router->makeFullUrl( 'aliases', 'index', 'edit', 'admin', array('id' => $row['id']) );
				}
				header( 'Content-Type: text/javascript; charset=utf-8' );
				echo json_encode( $jsonObj );
				return false;
			} catch ( Input_KeyNoExist $e ) {
				trigger_error( $e->getMessage(), E_USER_ERROR );
			}
		}

		/**
		 * Adds a new URL Alias to the database
		 *
		 * @return string
		 */
		public function addSection() {
			if ( !$this->_acl->check( 'aliases_add' ) ) {
				throw new Module_NoPermission;
			}
			$this->_i18n->textDomain( $this->textDomain() );
			$this->setTitle( t('Add URL Alias') );
			$this->setOutputType( self::_OT_CONFIG );
			// Create form
			$form = $this->aliasForm();
			if ( $form->hasInput() && $form->isValid() ) {
				$fd = $form->getValues( 'alias' );
				try {
					$this->_model()->add( $fd['alias'], $fd['url'], $fd['redirect'] );
					$this->_event->success( t('Added URL Alias') );
					return zula_redirect( $this->_router->makeUrl( 'aliases' ) );
				} catch ( Alias_AlreadyExists $e ) {
					$this->_event->error( sprintf( t('URL Alias "%1$s" already exists'), $fd['alias'] ) );
				}
			}
			return $form->getOutput();
		}

		/**
		 * Edit a URL Alias (Update the URL and/or Alias name)
		 *
		 * @return string
		 */
		public function editSection() {
			if ( !$this->_acl->check( 'aliases_edit' ) ) {
				throw new Module_NoPermission;
			}
			$this->_i18n->textDomain( $this->textDomain() );
			$this->setOutputType( self::_OT_CONFIG );
			// Get ID of the alias to edit
			try {
				$id = $this->_router->getArgument( 'id' );
				$details = $this->_model()->getDetails( $id );
				$this->setTitle( t('Edit URL Alias') );
				// Setup the form with validation
				$form = $this->aliasForm( $details['alias'], $details['url'], $details['redirect'], $details['id'] );
				if ( $form->hasInput() && $form->isValid() ) {
					$fd = $form->getValues( 'alias' );
					if ( $fd['alias'] != $details['alias'] && $this->_model()->aliasExists( $fd['alias'], Aliases_Model::_NAME ) ) {
						$this->_event->error( sprintf( t('URL Alias "%1$s" already exists'), $fd['alias'] ) );
					} else {
						$this->_model()->edit( $details['id'], $fd['alias'], $fd['url'], $fd['redirect'] );
						$this->_event->success( t('Edited URL alias') );
						return zula_redirect( $this->_router->makeUrl( 'aliases' ) );
					}
				}
				return $form->getOutput();
			} catch ( Router_ArgNoExist $e ) {
				$this->_event->error( t('No alias selected') );
			} catch ( Alias_NoExist $e ) {
				$this->_event->error( t('URL alias does not exist') );
			}
			return zula_redirect( $this->_router->makeUrl( 'aliases' ) );
		}

		/**
		 * Builds the edit/add form for the URL Aliases
		 *
		 * @param string $alias
		 * @param string $url
		 * @param int|bool $redirect
		 * @param int $id
		 * @return string
		 */
		protected function aliasForm( $alias=null, $url=null, $redirect=false, $id=null ) {
			$this->_i18n->textDomain( $this->textDomain() );
			// Make view form class and set operation
			$op = is_null($id) ? 'add' : 'edit';
			$form = new View_form( 'form.html', 'aliases', is_null($id) );
			if ( $op == 'add' ) {
				$form->action( $this->_router->makeUrl( 'aliases', 'index', $op ) );
			} else {
				$form->action( $this->_router->makeUrl( 'aliases', 'index', $op, null, array('id' => $id) ) );
			}
			// Add all of the validators in
			$form->addElement( 'alias/alias', $alias, t('Alias'), array(new Validator_Length(1, 255), new Validator_Alphanumeric('_-.!/')) );
			$form->addElement( 'alias/url', $url, 'URL', new Validator_Length(1, 255) );
			$form->addElement( 'alias/redirect', $redirect, t('Redirect'), new Validator_Bool );
			// Set op and return
			$form->assign( array('OP' => $op, 'ID' => $id) );
			return $form;
		}

		/**
		 * Deletes an alias by ID if it exists
		 *
		 * @return string
		 */
		public function deleteSection() {
			if ( !$this->_acl->check( 'aliases_delete' ) ) {
				throw new Module_NoPermission;
			} else if ( $this->_input->checkToken() ) {
				$this->_i18n->textDomain( $this->textDomain() );
				$this->setOutputType( self::_OT_CONFIG );
				try {
					$aliasId = $this->_input->post( 'alias_ids' );
					$this->_model()->delete( $aliasId );
					$this->_event->success( t('Deleted selected aliases') );
				} catch ( Input_KeyNoExist $e ) {
					$this->_event->error( t('No URL aliases selected') );
				}
			} else {
				$this->_event->error( Input::csrfMsg() );
			}
			return zula_redirect( $this->_router->makeUrl( 'aliases' ) );
		}

	}

?>
