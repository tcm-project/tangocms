<?php
/**
 * Zula Framework Registry
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @author Jefersson Nathan <malukenho@phpse.net>
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Registry
 */
class Registry {

    /**
     * Holds all the registered items within the registry
     * @var array
     */
    static private $registered = array();

    /**
     * Constructor function
     */
    private function __construct() {
    }

    /**
     * Register an object within the framework so that other classes
     * can access other classes easily without the need for globals
     *
     * @param string $name
     * @param object $object
     * @return bool
     */
    static public function register( $name, $object ) {
        if ( !is_object( $object ) || isset( self::$registered[ $name ] ) ) {
            trigger_error( 'Registry::register() unable to add item to registry. "'.$name.'" is not an object or is already registered', E_USER_NOTICE );
            return false;
        }
        if ( $object instanceof Zula_LibraryBase && !$object->getRegistryName() && method_exists( $object, '_onLoad' ) ) {
            $object->_onLoad( $name );
        }
        self::$registered[ $name ] = $object;
        return true;
    }

    /**
     * Removes an object from the registry
     *
     * @param string $name
     * @return bool
     */
    static public function unregister( $name ) {
        if ( isset( self::$registered[ $name ] ) ) {
            unset( self::$registered[ $name ] );
            return true;
        } else {
            return false;
        }
    }

    /**
     * Fetch an object from the registry
     *
     * @param string $name
     * @return object|bool
     */
    static public function get( $name ) {
        if ( isset( self::$registered[ $name ] ) ) {
            return self::$registered[ $name ];
        } else {
            throw new Exception( 'Registry::get() entry "'.$name.'" does not exist' );
        }
    }

    /**
     * Checks if an item exists in the registry
     *
     * @param string $name
     * @return bool
     */
    static public function has( $name ) {
        return isset( self::$registered[ $name ] );
    }

    /**
     * Returns every registerd item
     *
     * @return array
     */
    static public function registered() {
        return self::$registered;
    }

}
