<?php
// $Id: Html.php 2768 2009-11-13 18:12:34Z alexc $

/**
 * Zula Framework HTML
 * --- HTML helper library to create common HTML elements
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_HTML
 */

	class Html extends Zula_LibraryBase {

		/**
		 * Format to use for the HTML 'name' attribute
		 * @var string
		 */
		protected $nameAttrFormat = '%s';

		/**
		 * Constructor
		 * Sets the HTML name format to use
		 *
		 * @param string $namePrefix
		 * @return object
		 */
		public function __construct( $nameAttrFormat=null ) {
			if ( trim( $nameAttrFormat ) ) {
				$this->nameAttrFormat = (string) $nameAttrFormat;
			}
		}

		/**
		 * Gets the name attribute format to use
		 *
		 * @return string
		 */
		public function getNameFormat() {
			return $this->nameAttrFormat;
		}

		/**
		 * Makes the value for the 'name' attribute
		 *
		 * @param string $val
		 * @return string
		 */
		protected function makeNameAttr( $val ) {
			return sprintf( $this->getNameFormat(), zula_htmlspecialchars($val) );
		}

		/**
		 * Builds a 'select' element with options that are of every
		 * group within the framework
		 *
		 * @param string $name
		 * @param string $default	The value to be selected by default
		 * @param string $id
		 * @return string
		 */
		public function selectGroups( $name, $default=null, $id=null ) {
			$groups = array();
			foreach( $this->_ugmanager->getAllGroups() as $group ) {
				if ( $group['id'] != UGManager::_GUEST_ID ) {
					$groups[ $group['name'] ] = $group['id'];
				}
			}
			return $this->select( $name, $default, $groups, $id );
		}

		/**
		 * Builds a 'select' element with the options provided
		 * also allowing for a default selected option.
		 *
		 * @param string $name
		 * @param string $default	The value to be selected by default
		 * @param array $options
		 * @param string $id
		 * @return string
		 */
		public function select( $name, $default=null, array $options=array(), $id=null ) {
			$strOptions = '';
			foreach( $options as $key=>$val ) {
				if ( is_bool( $val ) ) {
					$val = zula_bool2str( $val );
				}
				$strOptions .= sprintf(
										'<option value="%1$s" %2$s>%3$s</option>',
										zula_htmlspecialchars( $val ),
										($val == $default ? 'selected="selected"' : ''),
										zula_htmlspecialchars( $key )
									 );
			}
			$id = trim($id) ? 'id="'.zula_htmlspecialchars($id).'"' : '';
			return sprintf( '<select name="%1s" %3$s>%2$s</select>', $this->makeNameAttr( $name ), $strOptions, $id );
		}

		/**
		 * Builds a 'input' element of type password
		 *
		 * @param string $name
		 * @param string $value
		 * @param string $id
		 * @return string
		 */
		public function inputPassword( $name, $value=null, $id=null ) {
			$value = is_bool($value) ? zula_bool2str( $value ) : zula_htmlspecialchars( $value );
			$id = trim($id) ? 'id="'.zula_htmlspecialchars($id).'"' : '';
			return sprintf( '<input type="password" name="%s" value="%s" %s>', $this->makeNameAttr($name), $value, $id );
		}

		/**
		 * Alias to input
		 *
		 * @see HTML::input()
		 */
		public function textbox( $name, $value=null, $id=null ) {
			return $this->input( $name, $value, $id );
		}

		/**
		 * Builds a 'input' element of type text
		 *
		 * @param string $name
		 * @param string $value
		 * @param string $id
		 * @return string
		 */
		public function input( $name, $value=null, $id=null ) {
			$value = is_bool($value) ? zula_bool2str( $value ) : zula_htmlspecialchars( $value );
			$id = trim($id) ? 'id="'.zula_htmlspecialchars($id).'"' : '';
			return sprintf( '<input type="text" name="%s" value="%s" %s>', $this->makeNameAttr($name), $value, $id );
		}

		/**
		 * Builds a 'textarea' element
		 *
		 * @param string $name
		 * @param string $value
		 * @param string $id
		 * @return string
		 */
		public function textarea( $name, $value=null, $id=null ) {
			$value = is_bool($value) ? zula_bool2str( $value ) : zula_htmlspecialchars( $value );
			$id = trim($id) ? 'id="'.zula_htmlspecialchars($id).'"' : '';
			return sprintf(
							'<textarea name="%s" cols="50" rows="20" %s>%s</textarea>',
							$this->makeNameAttr( $name ),
							$id,
							$value
						  );
		}

		/**
		 * Builds 'input' elements of type radio
		 *
		 * @param string name
		 * @param string $default
		 * @param array $options
		 * @return string
		 */
		public function radio( $name, $default=null, array $options=array() ) {
			$optionCount = count( $options );
			$format = '<input type="radio" id="%1$s" name="%2$s" %3$s value="%4$s"> <label class="horizontal" for="%1$s">%5$s</label>';
			$output = '';
			$i = 1;
			foreach( $options as $key=>$val ) {
				$val ==  is_bool($val) ? zula_bool2str($val) : zula_htmlspecialchars($val);
				$output .= sprintf(
									$format,
									uniqid( 'html_' ),
									$this->makeNameAttr( $name ),
									($val == $default ? 'checked="checked"' : ''),
									$val,
									zula_htmlspecialchars( $key )
								   );
				if ( $i++ != $optionCount ) {
					$output .= '<br>';
				}
			}
			return $output;
		}

		/**
		 * Builds 'input' elements of type checkbox
		 *
		 * @param string name
		 * @param string $default
		 * @param array $options
		 * @return string
		 */
		public function checkbox( $name, $default=null, array $options=array() ) {
			$optionCount = count( $options );
			$format = '<input type="checkbox" id="%1$s" name="%2$s[]" %3$s value="%4$s"> <label class="horizontal" for="%1$s">%5$s</label>';
			$output = '';
			$i = 1;
			foreach( $options as $key=>$val ) {
				$checked = in_array($val, (array) $default) ? 'checked="checked"' : '';
				$val ==  is_bool($val) ? zula_bool2str($val) : zula_htmlspecialchars($val);
				$output .= sprintf(
									$format,
									uniqid( 'html_' ),
									$this->makeNameAttr( $name ),
									$checked,
									$val,
									zula_htmlspecialchars( $key )
								   );
				if ( $i++ != $optionCount ) {
					$output .= '<br>';
				}
			}
			return $output;
		}

	}

?>
