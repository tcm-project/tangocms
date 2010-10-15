<?php

/**
 * Zula Framework Module
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Evangelos Foutras
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Installer
 */

	class Stage_controller_three extends Zula_ControllerBase {

		/**
		 * Gathers all details needed to connect to the database
		 * and create the initial tables to populate.
		 *
		 * @return bool|string
		 */
		public function indexSection() {
			$this->setTitle( t('SQL details') );
			/**
			 * Make sure user is not trying to skip ahead
			 */
			if ( !isset( $_SESSION['install_stage'] ) || $_SESSION['install_stage'] !== 3 ) {
				return zula_redirect( $this->_router->makeUrl('stage', 'one') );
			}
			$form = new View_Form( 'stage3/sql_form.html', 'stage' );
			$form->addElement( 'database', null, t('SQL Database'), new Validator_Length(1, 64) );
			$form->addElement( 'user', null, t('Username'), new Validator_Length(1, 16) );
			$form->addElement( 'pass', null, t('Password'), array(new Validator_Length(0, 64), new Validator_Regex('#^[^"]*$#')) );
			$form->addElement( 'port', 3306, t('SQL Port'), new Validator_Int );
			$form->addElement( 'host', 'localhost', t('SQL host'), new Validator_Length(1, 80) );
			$form->addElement( 'prefix', 'tcm_', t('Table prefix'), array(new Validator_Length(0, 32), new Validator_Alphanumeric('_-')) );
			if ( $form->hasInput() && $form->isValid() ) {
				$fd = $form->getValues();
				try {
					$sql = new SQL( 'mysql', $fd['database'], $fd['host'], $fd['user'], $fd['pass'], $fd['port'] );
					$sql->setPrefix( $fd['prefix'] );
					$sql->query( "SET NAMES 'utf8'" ); # Use UTF-8 character set for the connection
					$sql->loadSqlFile( $this->getPath().'/schema.sql' );
					/**
					 * Register SQL, load ACL and begin installing modules. The module dir
					 * needs to be changed to the 'real' module dir first, though.
					 */
					Registry::register( 'sql', $sql );
					$this->_zula->loadLib( 'acl' );
					if ( !Registry::has( 'config_sql' ) ) {
						$configSql = new Config_sql;
						$configSql->load( 'config' );
						Registry::register( 'config_sql', $configSql );
					}
					Module::setDirectory( _REAL_MODULE_DIR );
					foreach( Module::getModules( Module::_INSTALLABLE ) as $modname ) {
						$module = new Module( $modname );
						$module->install();
					}
					$this->setTcmDefaults();
					Module::setDirectory( $this->_zula->getDir( 'modules' ) );
					/**
					 * Attempt to update the configuration ini file with the new values
					 */
					$confKeys = array(
										'sql/enable'	=> true,
										'sql/host'		=> $fd['host'],
										'sql/user'		=> $fd['user'],
										'sql/pass'		=> $fd['pass'],
										'sql/database'	=> $fd['database'],
										'sql/type'		=> 'mysql',
										'sql/prefix'	=> $fd['prefix'],
										'sql/port'		=> $fd['port'],
										);
					if ( $this->_input->has( 'get', 'ns' ) ) {
						$confKeys['url_router/type'] = 'standard';
					}
					$this->_config_ini->update( array_keys($confKeys), array_values($confKeys) );
					try {
						// All is good, attempt to write and go to next stage
						$this->_config_ini->writeIni();
						$_SESSION['install_stage']++;
						return zula_redirect( $this->_router->makeUrl('stage', 'four') );
					} catch ( Config_ini_FileNotWriteable $e ) {
						$this->_event->error( $e->getMessage() );
					}
				} catch ( SQL_UnableToConnect $e ) {
					$this->_event->error( t('Unable to connect to, or select SQL database') );
				}
			}
			return $form->getOutput();
		}

		/**
		 * Sets up defaults for TangoCMS, such as ACL rules and
		 * default content for some modules.
		 *
		 * @return null
		 */
		protected function setTcmDefaults() {
			// Setup some common default roles that will be used
			$guestInherit = $adminInherit = array('group_root');
			foreach( $this->_acl->getRoleTree( 'group_guest', true ) as $role ) {
				array_unshift( $guestInherit, $role['name'] );
			}
			foreach( $this->_acl->getRoleTree( 'group_admin', true ) as $role ) {
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
				$this->_acl->allowOnly( $resource, $roles );
			}
			// Setup module load order
			if ( Module::exists( 'comments' ) ) {
				$tmpModule = new Module( 'comments' );
				$tmpModule->setLoadOrder( 1 ); # Should force it below Shareable by default
			}
		}

	}

?>
