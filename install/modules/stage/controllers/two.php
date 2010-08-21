<?php

/**
 * Zula Framework
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Evangelos Foutras
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Installer
 */
	class Stage_controller_two extends Zula_ControllerBase {

		/**
		 * Checks that all the needed PHP extensions are in place
		 * and the needed directories/files are writeable.
		 */
		public function indexSection() {
			$this->setTitle( t('Pre installation checks') );
			/**
			 * Make sure user is not trying to skip a head a stage.
			 */
			if ( !isset( $_SESSION['install_stage'] ) || $_SESSION['install_stage'] !== 2 ) {
				return zula_redirect( $this->_router->makeUrl( 'stage', 'one' ) );
			}
			// Create the different types of things we need to check
			$checks = array(
							'extensions'	=> array(
													'title'	=> t('Required PHP extensions'),
													'passed'=> false,
													'values'=> array(
																	'ctype', 'date', 'dom', 'hash', 'pdo',
																	'pdo_mysql', 'pcre', 'session', 'json',
																	)
													),
							'optional-ext'	=> array(
													'title'	=> t('Optional PHP extensions'),
													'passed'=> true,
													'values'=> array('gd')
													),
							'files'			=> array(
													'title'	=> t('Writable files'),
													'passed'=> false,
													'values'=> array(
																	$this->_zula->getDir( 'config' ).'/config.ini.php',
																	$this->_zula->getDir( 'config' ).'/layouts/admin-default.xml',
																	$this->_zula->getDir( 'config' ).'/layouts/main-default.xml',
																	$this->_zula->getDir( 'config' ).'/layouts/fpsc-admin.xml',
																	$this->_zula->getDir( 'config' ).'/layouts/fpsc-main.xml',
																	)
													),
							'directories'	=> array(
													'title'	=> t('Writable directories'),
													'passed'=> false,
													'values'=> array(
																	$this->_zula->getDir( 'config' ).'/layouts',
																	$this->_zula->getDir( 'logs' ),
																	$this->_zula->getDir( 'tmp' ),
																	$this->_zula->getDir( 'uploads' ),
																	$this->_zula->getDir( 'locale' ),
																	)
													),
						);
			// Run the checks
			$passed = true;
			foreach( $checks as $name=>$details ) {
				$results = array();
				foreach( $details['values'] as $val ) {
					switch( $name ) {
						case 'extensions':
						case 'optional-ext':
							$results[ $val ] = extension_loaded( $val );
							break;

						case 'files':
						case 'directories':
							$results[ $val ] = zula_is_writable( $val );
					}
				}
				if ( $name != 'optional-ext' && in_array( false, $results, true ) ) {
					$passed = false;
					$checks[ $name ]['passed'] = false;
				} else {
					$checks[ $name ]['passed'] = true;
				}
				$checks[ $name ]['values'] = $results;
			}
			if ( $passed ) {
				$this->_event->success( t('Pre-installation checks successful!') );
				$_SESSION['install_stage']++;
				return zula_redirect( $this->_router->makeUrl( 'stage', 'three' ) );
			} else {
				$this->_event->error( t('Please make sure you correct your installation so all checks below pass') );
				// Load view with the results
				$view = $this->loadView( 'stage2/checks.html' );
				$view->assign( array(
									'CHECKS'	=> $checks,
									'PASSED'	=> $passed,
									));
				return $view->getOutput();
			}
		}

	}

?>
