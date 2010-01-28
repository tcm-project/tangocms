<?php

/**
 * Zula Framework Base
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula
 */

	abstract class Zula_Base {

		/**
		 * Cache of every model that has been loaded
		 * @var array
		 */
		protected $models = array();

		/**
		 * Magic method for allowing easy access to libraries already
		 * loaded into the registery, e.g. instead of doing:
		 * -- $foo = Registry::get( 'foo' );
		 * -- $foo->bar();
		 * you can simply do the following:
		 * -- $this->_foo->bar();
		 *
		 * @param string $libName
		 * @return object
		 */
		public function __get( $library ) {
			if ( strpos( $library, '_' ) === 0 ) {
				try {
					$this->$library = Registry::get( substr( $library, 1 ) );
				} catch ( Exception $e ) {
					$this->$library = Registry::get( 'zula' )->loadLib( substr( $library, 1 ) );
				}
				return $this->$library;
			}
		}

		/**
		 * Loads a model for a given module
		 *
		 * @param string $model
		 * @param string $module
		 * @return object
		 */
		protected function _model( $model=null, $module='' ) {
			if ( isset( $this->models[ $module ][ $model ] ) ) {
				return $this->models[ $module ][ $model ];
			}
			// Create the path to the correct model directory
			$modPath = $this->_zula->getDir( 'modules' ).'/'.$module;
			if ( file_exists( $modPath.'/Exceptions.php' ) ) {
				include_once $modPath.'/Exceptions.php';
			}
			$path = $modPath.'/models/'.$model.'.php';
			if ( file_exists( $path ) ) {
				require_once $path;
				$className = $model.'_model';
				$tmpClass = new $className;
				if ( $tmpClass instanceof Zula_ModelBase ) {
					$this->models[ $module ][ $model ] = $tmpClass;
					return $tmpClass;
				} else {
					throw new Zula_ModelNoExist( 'Model "'.$model.'" does not extend Zula_ModelBase' );
				}
			} else {
				throw new Zula_ModelNoExist( 'Model file "'.$path.'" does not exist or is not readable' );
			}
		}

	}

?>
