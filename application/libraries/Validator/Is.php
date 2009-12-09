<?php

/**
 * Zula Framework Validator (Is)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Validator
 */

	class Validator_is extends Validator_Base {

		/**
		 * Data type or class instance needed
		 * @var string
		 */
		protected $type = null;

		/**
		 * Constructor
		 * Sets what data type (or class instance) the value
		 * should be to be valid.
		 *
		 * @param string $type
		 * @return object
		 */
		public function __construct( $type ) {
			switch( $type ) {
				case 'bool':
					$this->type = 'boolean';
					break;
				case 'int':
					$this->type = 'integer';
					break;
				case 'float':
					$this->type = 'double';
					break;
				case 'null':
				case null:
					$this->type = 'NULL';
					break;
				default:
					$this->type = (string) $type;
			}
		}

		/**
		 * Runs the needed checks to see if the value is valid. A non
		 * true value will be returned if it failed.
		 *
		 * @param mixed $value
		 * @return bool|string
		 */
		public function validate( $value ) {
			if (
				(gettype( $value ) == $this->type)
				||
				(is_object( $value ) && ($this->type == 'object' || $value instanceof $this->type))
			) {
				return true;
			} else {
				return sprintf( t('%%1$s must be of type %1$s'), $this->type );
			}
		}

	}

?>
