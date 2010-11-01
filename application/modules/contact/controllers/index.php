<?php

/**
 * Zula Framework Module
 * Backwards compatibility with TangoCMS <= 2.5.71
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Contact
 */

	class Contact_controller_index extends Zula_ControllerBase {

		/**
		 * Magic method allows for shorter URL by providing the
		 * contact form ID
		 *
		 * @param string $name
		 * @param array $args
		 * @return string|bool
		 */
		public function __call( $name, array $args ) {
			if ( !$this->inSector( 'SC' ) ) {
				return false;
			}
			$id = substr( $name, 0, -7 );
			if ( $id == 'index' ) {
				// Select the latest contact form and display that
				$pdoSt = $this->_sql->query( 'SELECT identifier FROM {PREFIX}mod_contact
											  ORDER BY id LIMIT 1' );
			} else {
				// Change the ID into the identifier
				$pdoSt = $this->_sql->prepare( 'SELECT identifier FROM {PREFIX}mod_contact
												WHERE id = :id' );
				$pdoSt->bindValue( ':id', $id, PDO::PARAM_INT );
				$pdoSt->execute();
			}
			$identifier = $pdoSt->fetch( PDO::FETCH_COLUMN );
			$pdoSt->closeCursor();
			if ( $identifier == false ) {
				throw new Module_ControllerNoExist;
			}
			return zula_redirect( $this->_router->makeUrl('contact', 'form', $identifier) );
		}

	}

?>