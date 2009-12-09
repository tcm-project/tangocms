<?php
// $Id: Validator.php 2768 2009-11-13 18:12:34Z alexc $

/**
 * Zula Framework Validator
 * Provides a way to chain many validation objects together, and test
 * against a single provided value.
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Validator
 */

	class Validator extends Zula_LibraryBase {

		/**
		 * Value to test against
		 * @var mixed
		 */
		protected $value = null;

		/**
		 * Title of the validation value
		 * @var string
		 */
		protected $title = null;

		/**
		 * Stored validators to use
		 * @var array
		 */
		protected $validators = array();

		/**
		 * Errors that may have been returned by the validations
		 * @var array
		 */
		protected $errors = array();

		/**
		 * Constructor.
		 * Sets the default value and title to test against
		 *
		 * @param mixed $value
		 * @param string $title
		 * @return object
		 */
		public function __construct( $value, $title ) {
			$this->value = $value;
			$this->title = (string) $title;
		}

		/**
		 * Add a new validator to use can either be an instance
		 * of Validator_Base, or a valid callback
		 *
		 * @param mixed $validator
		 * @return bool
		 */
		public function add( $validator ) {
			if ( is_object( $validator ) ) {
				if ( !($validator instanceof Validator_Base) ) {
					trigger_error( 'Validator::add() validator must extend "Validator_Base"', E_USER_WARNING );
					return false;
				}
			} else if ( !is_callable( $validator ) ) {
				trigger_error( 'Validator::add() value given is not a valid callback', E_USER_WARNING );
				return false;
			}
			$this->validators[] = $validator;
			return true;
		}

		/**
		 * Runs all the validators against the provided values.
		 * if values are provided here, it will use these instead
		 * of the defaults passed in the constructor.
		 *
		 * @param mixed $value
		 * @param string $title
		 * @return bool
		 */
		public function validate( $value=null, $title=null ) {
			$value = $value === null ? $this->value : $value;
			$title = $title === null ? $this->title : $title;
			foreach( $this->validators as $validator ) {
				$result = is_object($validator) ? $validator->validate( $value ) : call_user_func( $validator, $value );
				if ( $result !== true ) {
					if ( !is_array( $result ) ) {
						$result = array($result);
					}
					foreach( $result as $error ) {
						$this->errors[] = sprintf( $error, $title, $value );
					}
				}
			}
			return empty( $this->errors );
		}

		/**
		 * Returns every error that was caught when validating
		 *
		 * @return array
		 */
		public function getErrors() {
			return $this->errors;
		}

		/**
		 * Gets the value that was tested
		 *
		 * @return mixed
		 */
		public function getValue() {
			return $this->value;
		}

	}

?>
