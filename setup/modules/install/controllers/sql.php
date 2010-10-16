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
 * @package Zula_Setup
 */

	class Install_controller_sql extends Zula_ControllerBase {

		/**
		 * Gathers all details needed to connect to the database
		 * and create the initial tables to populate.
		 *
		 * The config.ini.php file also gets updated with the SQL
		 * details and others such as hashing salt and router type.
		 *
		 * @return bool|string
		 */
		public function indexSection() {
			$this->setTitle( t('SQL details') );
			/**
			 * Make sure user is not trying to skip ahead
			 */
			if ( !isset( $_SESSION['installStage'] ) || $_SESSION['installStage'] !== 3 ) {
				return zula_redirect( $this->_router->makeUrl('install', 'security') );
			}
			$form = new View_Form( 'sql.html', 'install' );
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
					 * Update config.ini.php file with the new values
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
									'hashing/salt'	=> zula_make_salt(),
									'acl/enable'	=> true
									);
					if ( $this->_input->has( 'get', 'ns' ) ) {
						$confKeys['url_router/type'] = 'standard';
					}
					$this->_config_ini->update( array_keys($confKeys), array_values($confKeys) );
					try {
						$this->_config_ini->writeIni();
						++$_SESSION['installStage'];
						return zula_redirect( $this->_router->makeUrl('install', 'user') );
					} catch ( Config_ini_FileNotWriteable $e ) {
						$this->_event->error( $e->getMessage() );
					}
				} catch ( SQL_UnableToConnect $e ) {
					$this->_event->error( t('Unable to connect to, or select SQL database') );
				}
			}
			return $form->getOutput();
		}

	}

?>
