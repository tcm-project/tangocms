<?php

/**
 * Zula Framework Hooks
 * --- Provides a simple Hooks/Plugins library for small hooks to extend
 * the current functionality
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Hooks
 */

	class Hooks {

		/**
		 * All modules which have registered hooks
		 * @var array
		 */
		static protected $loadedModules = array();

		/**
		 * All of the registers hooks, sorted by event
		 * @var array
		 */
		static protected $hooks = array();

		/**
		 * UHI counter
		 * @var int
		 */
		static protected $uhi = 0;

		/**
		 * An instance of this class should not be allowed
		 */
		private function __construct() {
		}

		/**
		 * Loads all hook listener file which will in turn
		 * register methods/functions to a hook
		 *
		 * @return int	Number of hook files loaded
		 */
		static public function load() {
			$acl = _ACL_ENABLED ? Registry::get( 'acl' ) : null;
			$hookPath = Registry::get( 'zula' )->getDir( 'modules' );
			$hookCount = 0;
			foreach( Module::getModules(Module::_INSTALLED, false, Module::_SORT_ORDER) as $module ) {
				if ( isset( $acl ) && $acl->resourceExists( $module.'_global' ) && $acl->check( $module.'_global' ) ) {
					$hook = $hookPath.'/'.$module.'/hooks/listeners.php';
					if ( is_file( $hook ) ) {
						include $hook;
						$class = $module.'_hooks';
						if ( class_exists( $class, false ) ) {
							new $class;
							$hookCount++;
							self::$loadedModules[] = $module;
						}
					}
				}
			}
			return $hookCount;
		}

		/**
		 * Registers a new hook to an event that may be get called
		 *
		 * A UHI (Unique Hook Identifier) will be returned (int)
		 *
		 * @param string 		$hook
		 * @param string|array 	$callback
		 * @param int			$priority
		 * @return int
		 */
		static public function register( $hook, $callback, $priority=null ) {
			if ( is_array( $callback ) ) {
				if ( isset( $callback[0], $callback[1] ) ) {
					$callback = array( $callback[0], (string) $callback[1] );
					if ( !is_callable( $callback ) ) {
						throw new Hooks_InvalidCallback( 'hook callback is not callable' );
					}
				} else {
					throw new Hooks_InvalidCallback( 'hook callback method is invalid, not enough data' );
				}
			} else if ( !function_exists( $callback ) ) {
				throw new Hooks_InvalidCallback( 'hook callback function "'.$callback.'" does not exist' );
			}
			$uhi = self::$uhi++;
			self::$hooks[ strtolower($hook) ][ $uhi ] = array(
															'callback'	=> $callback,
															'priority'	=> $priority,
															);
			return $uhi;
		}

		/**
		 * Removes a registered hook via the UHI
		 *
		 * @param int $uhi
		 * @return bool
		 */
		static public function remove( $uhi ) {
			foreach( self::$hooks as &$event ) {
				if ( isset( $event[ $uhi ] ) ) {
					unset( $event[ $uhi ] );
					return true;
				}
			}
			return false;
		}

		/**
		 * Gets all registered modules names
		 *
		 * @return array
		 */
		static public function getLoadedModules() {
			return self::$loadedModules;
		}

		/**
		 * Notifies all registerd hooks of the specified event
		 * that the event has occured and they need to respond
		 *
		 * @param string $hookEvent
		 * @param mixed ...
		 * @return mixed
		 */
		static public function notifyAll() {
			if ( func_num_args() ) {
				$funcArgs = func_get_args();
				$event = strtolower( array_shift( $funcArgs ) );
				if ( empty( self::$hooks[ $event ] ) ) {
					return null;
				}
				$result = array();
				foreach( self::$hooks[ $event ] as $hook ) {
					$tmpResult = call_user_func_array( $hook['callback'], $funcArgs );
					if ( $tmpResult !== null ) {
						if ( is_array( $tmpResult ) ) {
							$result = array_merge_recursive( $result, $tmpResult );
						} else {
							$result[] = $tmpResult;
						}
					}
				}
				return $result;
			} else {
				trigger_error( 'Hooks::notifyAll() wrong argument count, expects at least one argument', E_USER_WARNING );
				return false;
			}
		}

		/**
		 * Notifies a single registered hook for the specified event
		 * one at a time. This function should be used within a loop,
		 * such as a while loop.
		 *
		 * Useful function when you need to change arguments for hooks
		 *
		 * @param string $hookEvent
		 * @param ...
		 * @return mixed
		 */
		static public function notify() {
			static $count, $lastEvent, $lastCb;
			if ( func_num_args() ) {
				$funcArgs = func_get_args();
				$event = strtolower( array_shift( $funcArgs ) );
				if ( $event != $lastEvent ) {
					$count = 0;
					$lastEvent = $event;
				}
				if ( empty( self::$hooks[ $event ] ) ) {
					$count = 0;
					return null;
				} else {
					$hook = array_slice( self::$hooks[ $event ], $count, 1 );
					if ( empty( $hook ) ) {
						$count = 0;
						return null;
					} else if ( $count > 500 && $hook[0]['callback'] == $lastCb ) {
						trigger_error( 'Hooks::notify() infinite loop detected for hook "'.$event.'"', E_USER_ERROR );
					} else {
						++$count;
						$lastCb = $hook[0]['callback']; # Keep track of last callback
						return call_user_func_array( $lastCb, $funcArgs );
					}
				}
			} else {
				trigger_error( 'Hooks::notify() wrong argument count, expects at least one argument', E_USER_WARNING );
				$count = 0;
				return false;
			}
		}

	}

?>
