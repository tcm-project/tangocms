<?php

/**
 * Zula Framework Model (contact)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Contact
 */

	class Contact_model extends Zula_ModelBase {

		/**
 		 * Contains how many contact forms would have been
		 * returned with no limit
 		 * @var int
 		 */
 		protected $formCount = null;

		/**
		 * Gets details for every contact form that exists, and can
		 * return a subset of the results. Can also check if user has
		 * access to the form.
		 *
		 * @param int $limit
		 * @param int $offset
		 * @param bool $aclCheck
		 * @return array
		 */
		public function getAllForms( $limit=0, $offset=0, $aclCheck=true ) {
			$statement = 'SELECT SQL_CALC_FOUND_ROWS * FROM {PREFIX}mod_contact ORDER BY name ASC';
			if ( $limit != 0 || $offset != 0 ) {
				// Limit the result set.
				$params = array();
				if ( $limit > 0 ) {
					$statement .= ' LIMIT :limit';
					$params[':limit'] = $limit;
				} else if ( $limit == 0 && $offset > 0 ) {
					$statement .= ' LIMIT 1000000';
				}
				if ( $offset > 0 ) {
					$statement .= ' OFFSET :offset';
					$params[':offset'] = $offset;
				}
				// Prepare and execute query
				$pdoSt = $this->_sql->prepare( $statement );
				foreach( $params as $ident=>$val ) {
					$pdoSt->bindValue( $ident, (int) $val, PDO::PARAM_INT );
				}
				$pdoSt->execute();
			} else {
				$cacheKey = 'contact_forms'; # Used later on as well
				$forms = $this->_cache->get( $cacheKey );
				if ( $forms == false ) {
					$pdoSt = $this->_sql->query( $statement );
				} else {
					$this->formCount = count( $forms );
				}
			}
			if ( isset( $pdoSt ) ) {
				$forms = array();
				foreach( $pdoSt->fetchAll( PDO::FETCH_ASSOC ) as $row ) {
					$forms[ $row['id'] ] = $row;
				}
				$pdoSt->closeCursor();
				$query = $this->_sql->query( 'SELECT FOUND_ROWS()' );
				$this->formCount = $query->fetch( PDO::FETCH_COLUMN );
				$query->closeCursor();
				if ( isset( $cacheKey ) ) {
					$this->_cache->add( $cacheKey, $forms );
				}
			}
			if ( $aclCheck ) {
				foreach( $forms as $tmpForm ) {
					$resource = 'contact-form-'.$tmpForm['id'];
					if ( !$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource ) ) {
						unset( $forms[ $tmpForm['id'] ] );
						--$this->formCount;
					}
				}
			}
			return $forms;
		}

		/**
		 * Gets the number of contact forms which would have been returned if
		 * Contact_Model::getAllForms() had no limit/offset args
		 *
		 * @return int|null
		 */
		public function getCount() {
			$count = $this->formCount;
			$this->formCount = null;
			return $count;
		}

		/**
		 * Checks if a contact form exists by ID or identifier
		 *
		 * @param int|string $form
		 * @param bool $byId
		 * @return bool
		 */
		public function formExists( $form, $byId=true ) {
			try {
				$this->getForm( $form );
				return true;
			} catch ( Exception $e ) {
				return false;
			}
		}

		/**
		 * Gets details for a contact form by ID or identifier
		 *
		 * @param int|string $form
		 * @param bool $byId
		 * @return array
		 */
		public function getForm( $form, $byId=true ) {
			$cacheKey = $byId ? null : 'contact_form_'.$form;
			if ( !($details = $this->_cache->get($cacheKey)) ) {
				$col = $byId ? 'id' : 'identifier';
				$pdoSt = $this->_sql->prepare( 'SELECT * FROM {PREFIX}mod_contact WHERE '.$col.' = ?' );
				$pdoSt->execute( array($form) );
				$details = $pdoSt->fetch( PDO::FETCH_ASSOC );
				$pdoSt->closeCursor();
				if ( $details ) {
					$this->_cache->add( $cacheKey, $details );
				} else {
					throw new Contact_NoExist( $form );
				}
			}
			return $details;
		}

		/**
		 * Gets all fields for a given form.
		 *
		 * @param int $fid
		 * @return array
		 */
		public function getFormFields( $fid ) {
			$cacheKey = 'contact_fields_'.$fid;
			if ( !($fields = $this->_cache->get($cacheKey)) ) {
				$fields = $this->_sql->query( 'SELECT * FROM {PREFIX}mod_contact_fields
											   WHERE form_id = '.(int) $fid.' ORDER BY `order`, id ASC' )
									 ->fetchAll( PDO::FETCH_ASSOC );
				$this->_cache->add( $cacheKey, $fields );
			}
			return $fields;
		}

		/**
		 * Gets details for a single form field by ID
		 *
		 * @param int $id
		 * @return array
		 */
		public function getField( $id ) {
			$field = $this->_sql->query( 'SELECT * FROM {PREFIX}mod_contact_fields WHERE id = '.(int) $id )
								->fetch( PDO::FETCH_ASSOC );
			if ( $field ) {
				return $field;
			} else {
				throw new Contact_FieldNoExist( $id );
			}
		}

		/**
		 * Adds a new contact form
		 *
		 * @param string $name
		 * @param string $email
		 * @param string $body
		 * @return array|bool
		 */
		public function addForm( $name, $email, $body ) {
			$i = null;
			do {
				try {
					$identifier = zula_clean($name).$i;
					$this->getForm( $identifier, false );
					++$i;
				} catch ( Contact_NoExist $e ) {
					break;
				}
			} while( true );
			$pdoSt = $this->_sql->prepare( 'INSERT INTO {PREFIX}mod_contact (name, identifier, email, body)
											VALUES(?, ?, ?, ?)' );
			$pdoSt->execute( array($name, $identifier, $email, $body) );
			$pdoSt->closeCursor();
			if ( $pdoSt->rowCount() ) {
				$id = $this->_sql->lastInsertId();
				$this->_cache->delete( 'contact_forms' );
				Hooks::notifyAll( 'contact_add_form', $id, $name, $identifier, $email, $body );
				return array(
							'id'	 		=> $id,
							'identifier'	=> $identifier,
							);
			} else {
				return false;
			}
		}

		/**
		 * Edits a contact form
		 *
		 * @param int $id
		 * @param string $name
		 * @param string $email
		 * @param string $body
		 * @return bool
		 */
		public function editForm( $fid, $name, $email, $body ) {
			$form = $this->getForm( $fid );
			$pdoSt = $this->_sql->prepare( 'UPDATE {PREFIX}mod_contact
											SET name = ?, email = ?, body = ? WHERE id = ?' );
			$pdoSt->execute( array($name, $email, $body, $form['id']) );
			$this->_cache->delete( array('contact_forms', 'contact_form_'.$form['identifier']) );
			Hooks::notifyAll( 'contact_edit_form', $form, $name, $email, $body );
			return true;
		}

		/**
		 * Deletes a contact form and all fields under it
		 *
		 * @param int $fid
		 * @return bool
		 */
		public function deleteForm( $fid ) {
			$form = $this->getForm( $fid );
			$query = $this->_sql->query( 'DELETE FROM {PREFIX}mod_contact WHERE id = '.(int) $form['id'] );
			$query->closeCursor();
			if ( $query->rowCount() ) {
				$this->_acl->deleteResource( 'contact-form-'.$form['id'] );
				$query = $this->_sql->query( 'DELETE FROM {PREFIX}mod_contact_fields WHERE form_id = '.(int) $form['id'] );
				$query->closeCursor();
				// Remove all cache
				$this->_cache->delete( array('contact_forms', 'contact_form_'.$form['identifier'], 'contact_fields_'.$form['id']) );
				Hooks::notifyAll( 'contact_delete_form', $form['id'] );
			} else {
				return false;
			}
		}

		/**
		 * Adds a new field to the provided form
		 *
		 * @param int 		$fid
		 * @param string 	$name
		 * @param bool 		$required
		 * @param string 	$type
		 * @param string 	$options
		 * @return int|bool
		 */
		public function addField( $fid, $name, $required=false, $type='textbox', $options='' ) {
			$pdoSt = $this->_sql->prepare( 'INSERT INTO {PREFIX}mod_contact_fields (form_id, name, required, type, options)
											VALUES(?, ?, ?, ?, ?)' );
			$pdoSt->execute( array($fid, $name, $required, $type, $options) );
			$pdoSt->closeCursor();
			if ( $pdoSt->rowCount() ) {
				$this->_cache->delete( 'contact_fields_'.$fid );
				return $this->_sql->lastInsertId();
			} else {
				return false;
			}
		}

		/**
		 * Edits an existing field
		 *
		 * @param int 		$id
		 * @param string 	$name
		 * @param bool 		$required
		 * @param string 	$type
		 * @param string 	$options
		 * @return bool
		 */
		public function editField( $id, $name, $required=false, $type='textbox', $options='' ) {
			$field = $this->getField( $id );
			$pdoSt = $this->_sql->prepare( 'UPDATE {PREFIX}mod_contact_fields SET name = ?, required = ?,
											type = ?, options = ? WHERE id = ?' );
			$pdoSt->execute( array($name, $required, $type, $options, $field['id']) );
			$this->_cache->delete( 'contact_fields_'.$field['form_id'] );
			return true;
		}

		/**
		 * Delete a form field
		 *
		 * @param int $id
		 * @return bool
		 */
		public function deleteField( $id ) {
			$field = $this->getField( $id );
			$query = $this->_sql->query( 'DELETE FROM {PREFIX}mod_contact_fields WHERE id = '.(int) $field['id'] );
			$query->closeCursor();
			if ( $query->rowCount() ) {
				$this->_cache->delete( 'contact_fields_'.$field['form_id'] );
				return true;
			} else {
				return false;
			}
		}

	}

?>
