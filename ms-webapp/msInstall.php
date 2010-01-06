<?php

/**
 * Microsoft Web App Gallery (Web PI/WDT) installation
 * --- Web PI/WDT doesn't allow us to do everything needed (easily) for the installation
 * therefor we need to do some last minute tasks, such as installing all modules. Once done
 * this file will delete its self, and refresh - the user wont notice anything, and everything
 * will be ready to use.
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package TangoCMS
 */

	$sql = Registry::get( 'sql' );
	$rootDetails = $sql->query( 'SELECT `password`, email FROM {SQL_PREFIX}users WHERE id = 2' )
					   ->fetch( PDO::FETCH_ASSOC );
	// Create a new random salt, and hash the plaintext password from the installer.
	try {
		$configIni = Registry::get( 'config_ini' );
		$configIni->update( 'hashing/salt', zula_make_salt() );
		$configIni->writeIni();
	} catch ( Exception $e ) {
	}
	$sql->query( 'UPDATE {SQL_PREFIX}users SET `password` = "'.zula_hash($rootDetails['password']).'"' );

	// Install all modules
	foreach( Module::getModules( Module::_INSTALLABLE ) as $modname ) {
		$module = new Module( $modname );
		$module->install();
		if ( $modname == 'comments' ) {
			$module->setLoadOrder( 1 ); # Should force it below Shareable by default
		} else if ( $modname == 'contact' ) {
			// Update the contact form email address to that of the first user
			$sql->query( 'UPDATE {SQL_PREFIX}mod_contact SET email = "'.$rootDetails['email'].'"' );
		}
	}

	// Update ACL resources for the default content layouts
	$guestInherit = $adminInherit = array('group_root');
	foreach( $acl->getRoleTree( 'group_guest', true ) as $role ) {
		array_unshift( $guestInherit, $role['name'] );
	}
	foreach( $acl->getRoleTree( 'group_admin', true ) as $role ) {
		array_unshift( $adminInherit, $role['name'] );
	}
	$aclResources = array(
						# main-default content layout
						'layout_controller_456'	=> $guestInherit,
						'layout_controller_974'	=> $guestInherit,
						'layout_controller_110'	=> $guestInherit,
						'layout_controller_119'	=> $guestInherit,
						'layout_controller_168'	=> $guestInherit,

						# admin-default content layout
						'layout_controller_409'	=> $adminInherit,
						'layout_controller_123'	=> $adminInherit,
						'layout_controller_909'	=> $adminInherit,
						'layout_controller_551'	=> $adminInherit,
						);
	foreach( $aclResources as $resource=>$roles ) {
		$acl->allowOnly( $resource, $roles );
	}

	// Cleanup, remove this file and redirect to the TangoCMS website
	Registry::get( 'cache' )->purge();
	unlink( __FILE__ );
	return zula_redirect( $router->makeUrl('/') );

?>