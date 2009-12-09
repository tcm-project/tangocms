<?php
// $Id: Hookbase.php 2768 2009-11-13 18:12:34Z alexc $

/**
 * Zula Framework base abstract hook class
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula
 */

	abstract class Zula_HookBase extends Zula_Base {

		/**
		 * Name of module these hooks are for
		 * @var string
		 */
		protected $module = null;
		
		/**
		 * Construtor
		 *
		 * Gets all of the methods for child (passed in) and register
		 * all of the ones that are prefixed with 'hook_'
		 *
		 * @param object $child
		 * @return object
		 */
		public function __construct( Zula_HookBase $child ) {
			$this->_log->message( 'hooks: attempting to register hooks for "'.get_class( $child ).'"', Log::L_DEBUG );
			$this->module = strtolower( substr( get_class($child), 0, - 6 ) );
			foreach( get_class_methods( $child ) as $method ) {
				if ( substr( $method, 0, 4 ) == 'hook' ) {
					$hookName = preg_split( '#([A-Z][^A-Z]*)#', $method, -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE );
					array_shift( $hookName );
					Hooks::register( strtolower( implode('_', $hookName) ), array($child, $method) );
				}
			}
		}
		
		/**
		 * Loads a model for the current module, or for a specified module.
		 *
		 * @param string $model
		 * @param string $module
		 * @return object
		 */
		protected function _model( $model=null, $module='' ) {
			if ( $module !== null && !trim( $module ) ) {
				$module = $this->module;
			}
			if ( $model === null ) {
				$model = $this->module;
			}
			return parent::_model( $model, $module );
		}

	}

?>
