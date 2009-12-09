<?php

/**
 * Zula Framework View Form
 * Allows forms to easily intergrate with the Validation libraries
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_View
 */

	class View_Form extends View {

		const
			/**
			 * Constants used to say where the data is coming from
			 */
			_POST			= 1,
			_GET			= 2;

		/**
		 * Should validation errors be automatically stored in the Event lib?
		 * @var bool
		 */
		protected $storeErrors = true;

		/**
		 * URL to the content this form is editing (or adding)
		 * @var string
		 */
		protected $contentUrl = '';

		/**
		 * Details of all elements that will be handled
		 * @var array
		 */
		protected $elements = array();

		/**
		 * Stores all missing form elements
		 * @var array
		 */
		protected $missing = null;

		/**
		 * Stored input values
		 * @var array
		 */
		protected $values = array();

		/**
		 * Holds validation status of the form
		 * @var bool
		 */
		protected $valid = true;

		/**
		 * Should antispam be used for this form?
		 * @var bool
		 */
		protected $antispam = false;

		/**
		 * Add CSRF token?
		 * @var book
		 */
		protected $csrfToken = true;

		/**
		 * Callback functions used when the form was reported as successful
		 * @var array
		 */
		protected $successCallbacks = array();

		/**
		 * Constructor
		 * Attempts to load the view file and sets which module this is for
		 * also will set if this form is used to add, or edit content.
		 *
		 * @param string $viewFile
		 * @param string $module
		 * @param bool $forAdd
		 * @param bool $storeErrors
		 * @return object
		 */
		public function __construct( $viewFile='', $module='', $forAdd=true, $storeErrors=true ) {
			parent::__construct( $viewFile, $module );
			$this->storeErrors = (bool) $storeErrors;
			$this->formType = (bool) $forAdd ? 'add' : 'edit';
		}

		/**
		 * Assigns the form action tag
		 *
		 * @param string $url
		 * @return object
		 */
		public function action( $url ) {
			$this->assign( array('ACTION' => (string) $url) );
			return $this;
		}

		/**
		 * Toggle Captcha for this form
		 *
		 * @deprecated deprecated since 0.7.7, use View_form::antispam().
		 * @param bool $captcha
		 * @return object
		 */
		public function captcha( $captcha ) {
			return $this->antispam( $captcha );
		}

		/**
		 * Toggles if Antispam methods should be used for this form
		 *
		 * @param bool $enable
		 * @return object
		 */
		public function antispam( $enable=true ) {
			$this->antispam = (bool) $enable;
			return $this;
		}

		/**
		 * Toggles CSRF token embeding
		 *
		 * @param bool $csrfToken
		 * @return object
		 */
		public function csrfToken( $csrfToken ) {
			$this->csrfToken = (bool) $csrfToken;
			return $this;
		}

		/**
		 * Sets the content URL that this form is adding/editing
		 *
		 * @param string $url
		 * @return bool
		 */
		public function setContentUrl( $url ) {
			$this->contentUrl = (string) $url;
			Hooks::notifyAll( 'view_form_set_content_url', Module::getLoading(), $url );
			return true;
		}

		/**
		 * Adds a new element (such as an input box, select etc) that will
		 * be validated and handled correctly.
		 *
		 * This does *not* add an element into the HTML, this library never
		 * touches anything to do with the HTML - this method simply adds
		 * it to be validated.
		 *
		 * @param string $inputName		'name' value of the HTML element
		 * @param string $defaultVal	Default value for the input element
		 * @param string $title			Title to be used in the Validation error msg
		 * @param mixed $validators
		 * @param bool $required		Is the field required to have a value?
		 * @param int $source			Where the data is to come from, GET or POST
		 * @param bool $forHtml 		Should HTML be allowed in the tag value?
		 * @return bool
		 */
		public function addElement( $inputName, $defaultVal=null, $title=null, $validators=null, $required=true, $source=self::_POST, $forHtml=false ) {
			$details = array(
							'input_name'	=> $inputName,
							'default'		=> $defaultVal,
							'title'			=> $title,
							'required'		=> (bool) $required,
							'source'		=> $source == self::_GET ? 'get' : 'post',
							'validators'	=> is_array($validators) ? $validators : array($validators),
							);
			// Assign needed tags
			try {
				$value = $this->_input->get( $details['input_name'], $details['source'] );
			} catch ( Input_KeyNoExist $e ) {
				$value = $details['default'];
			}
			foreach( array_reverse( preg_split('#(?<!\\\)/#', trim($details['input_name'], '/')) ) as $v ) {
				$value = array( $v => $value );
			}
			if ( $forHtml ) {
				$this->assignHtml( $value );
			} else {
				$this->assign( $value );
			}
			$this->elements[] = $details;
			return true;
		}

		/**
		 * Adds a new element that allows HTML within its content.
		 *
		 * @see View_Form::addElement()
		 * @return bool
		 */
		public function addHtmlElement( $inputName, $defaultVal=null, $title=null, $validators=null, $required=true, $source=self::_POST ) {
			return $this->addElement( $inputName, $defaultVal, $title, $validators, $required, $source, true );
		}

		/**
		 * Checks if all the needed POST and GET data is there.
		 *
		 * @return bool
		 */
		public function hasInput() {
			$this->loadAmc(); # Load additional module content
			// Check all elements are present
			foreach( $this->elements as $element ) {
				if ( $element['required'] === true && !$this->_input->has( $element['source'], $element['input_name'] ) ) {
					$this->missing[] = $element['input_name'];
				}
			}
			return empty($this->missing);
		}

		/**
		 * Loads the additional module content, such as table forms
		 *
		 * @return int
		 */
		protected function loadAmc() {
			$amcTable = '<table>
							<thead>
								<tr>
									<th colspan="2">'.t('Additional Content', Locale::_DTD).'</th>
								</tr>
							</thead>
							<tbody>';
			$htmlLib = new Html( 'amcForm[%s]' );
			$rowFormat = '<tr class="%1$s">
							<td>
								<dl>
									<dt>%2$s</dt>
									<dd>%3$s</dd>
								</dl>
							</td>
							<td class="confcol">%4$s</td>
						  </tr>';
			$i = 0;
			while( $amcElements = Hooks::notify('amc_form_table', Module::getLoading(), $this->formType, $this->contentUrl) ) {
				if ( isset( $amcElements['onSuccess'] ) ) {
					$this->successCallbacks[] = $amcElements['onSuccess'];
				}
				foreach( (array) $amcElements['inputs'] as $input ) {
					if ( !isset( $input['required'] ) ) {
						$input['required'] = true;
					}
					$inputKey = 'amcForm/'.$input['args'][0];
					if ( $this->_input->has( 'post', $inputKey ) ) {
						// Attempt to get the value form the previous form if it failed
						$input['args'][1] = $this->_input->post( $inputKey );
					}
					$amcTable .= sprintf(
										$rowFormat,
										zula_odd_even( $i++ ),
										$input['name'],
										$input['desc'],
										call_user_func_array( array($htmlLib, $input['type']), $input['args'] )
										);
					$this->addElement( $inputKey, '', $input['name'], $input['validators'], $input['required'] );
				}
			}
			if ( $i === 0 ) {
				$this->assign( array('AMC' => array('TABLE' => '')) );
				return 0;
			} else {
				$amcTable .= '</tbody></table>';
				$this->assignHtml( array(
										'AMC' => array('TABLE' => $amcTable)
										));
				return $i;
			}
		}

		/**
		 * Returns an array of all missing elements
		 *
		 * @return array
		 */
		public function getMissing() {
			if ( $this->missing === null ) {
				$this->hasInput();
			}
			return (array) $this->missing;
		}

		/**
		 * Quickly adds in the antispam if needed
		 *
		 * @param bool $parseConfigTags
		 * @return string
		 */
		public function getOutput( $parseConfigTags=false ) {
			if ( $this->antispam ) {
				$antispam = new Antispam;
				if ( ($form = $antispam->create()) !== false ) {
					$this->assignHtml( array('ANTISPAM' => $form) );
				} else {
					$this->assignHtml( array('ANTISPAM' => '') );
					$this->_event->error( t('Unable to create antispam, please check the logs.', Locale::_DTD) );
				}
			}
			$output = parent::getOutput( $parseConfigTags );
			if ( $this->csrfToken === true ) {
				$output = preg_replace_callback( '#</form>#i', array($this, 'csrfReplace'), $output );
			}
			return $output;
		}

		/**
		 * Embeds a hidden input element for automatic CSRF
		 * attack protection
		 *
		 * @param array $matches
		 * @return string
		 */
		protected function csrfReplace( $matches ) {
			return $this->_input->createToken( true ).'</form>';
		}

		/**
		 * Runs all of the validation checks on the elements using the
		 * validatiors that are stored
		 *
		 * @return bool
		 */
		public function isValid() {
			if ( $this->csrfToken === true && !$this->_input->checkToken() ) {
				// CSRF protection failed!
				if ( $this->storeErrors === true ) {
					$this->_event->error( Input::csrfMsg() );
				}
				return false;
			}
			foreach( $this->elements as $element ) {
				try {
					$value = $this->_input->get( $element['input_name'], $element['source'] );
				} catch ( Input_KeyNoExist $e ) {
					if ( $element['required'] === true ) {
						throw $e;
					} else {
						continue;
					}
				}
				// Store the input names value correclty as a multi-dimensional array
				$tmpVal = $value;
				foreach( array_reverse( preg_split('#(?<!\\\)/#', trim($element['input_name'], '/')) ) as $v ) {
					$tmpVal = array( $v => $tmpVal );
				}
				$this->values = zula_merge_recursive( $this->values, $tmpVal );
				$count = is_array($value) ? count($value) : strlen($value);
				if ( $element['required'] === false && $count == 0 ) {
					continue;
				}
				// Check if it is valid
				$validator = new Validator( $value, $element['title'] );
				foreach( $element['validators'] as $tmpValidator ) {
					$validator->add( $tmpValidator );
				}
				if ( $validator->validate() === false ) {
					$this->valid = false;
					if ( $this->storeErrors === true ) {
						// Store all errors (if any)
						foreach( $validator->getErrors() as $error ) {
							$this->_event->error( $error );
						}
					}
				}
			}
			// Check if the antispam was successful, if enabled
			if ( $this->valid && $this->antispam === true ) {
				$antispam = new Antispam;
				if ( !$antispam->check() ) {
					$this->valid = false;
					if ( $this->storeErrors === true ) {
						$this->_event->error( t('Sorry, incorrect answer to the captcha', Locale::_DTD) );
					}
				}
			}
			return $this->valid;
		}

		/**
		 * Gets all values that were tested from the form, or
		 * for a specified value.
		 *
		 * @param string $key
		 * @return array
		 */
		public function getValues( $key=null ) {
			if ( trim( $key ) ) {
				$key = preg_split( '#(?<!\\\)/#', trim($key, '/') );
				foreach( $key as $val ) {
					$val = stripslashes( $val );
					if ( !isset( $tmpVal ) && isset( $this->values[ $val ] ) ) {
						$tmpVal = $this->values[ $val ];
					} else if ( isset( $tmpVal[ $val ] ) ) {
						$tmpVal = $tmpVal[ $val ];
					} else {
						throw new View_FormValueNoExist( 'form key "'.implode( '/', $key ).'" does not exist' );
					}
				}
				return $tmpVal;
			} else {
				return $this->values;
			}
		}

		/**
		 * Confirms that the form has been sucessfuly, and will
		 * call all of the callbacks with the form values, and
		 * the content URL that had been edited/added if available
		 *
		 * @param string $contentUrl
		 * @return bool
		 */
		public function success( $contentUrl='' ) {
			if ( trim( $contentUrl ) ) {
				$this->contentUrl = $contentUrl;
			}
			foreach( $this->successCallbacks as $cbf ) {
				call_user_func( $cbf, $this->values, $this->contentUrl );
			}
			return true;
		}

	}

?>
