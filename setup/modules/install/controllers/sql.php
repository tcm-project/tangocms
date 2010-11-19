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
			if (
				$this->_zula->getMode() != 'cli' &&
				(!isset( $_SESSION['installStage'] ) || $_SESSION['installStage'] !== 2)
			) {
				return zula_redirect( $this->_router->makeUrl('install', 'checks') );
			}
			// Get data from either a form or CLI arguments
			if ( $this->_zula->getMode() == 'cli' ) {
				$dsn = parse_url( $this->_input->cli('dsn') );
				if ( isset( $dsn['scheme'], $dsn['host'], $dsn['user'], $dsn['path'] ) ) {
					$data = array(
								'type'	=> $dsn['scheme'],
								'user'	=> $dsn['user'],
								'pass'	=> isset($dsn['pass']) ? $dsn['pass'] : '',
								'host'	=> $dsn['host'],
								'port'	=> isset($dsn['port']) ? $dsn['port'] : 3306,
								'dbname'=> ltrim( $dsn['path'], '/' ),
								'prefix'=> $this->_input->cli( 'dbPrefix' ),
								);
				} else {
					$this->_event->error( t('Invalid DSN string') );
					$this->_zula->setExitCode( 3 );
					return false;
				}
			} else {
				$form = new View_Form( 'sql.html', 'install' );
				$form->addElement( 'user', null, t('Username'), new Validator_Length(1, 16) );
				$form->addElement( 'pass', null, t('Password'), array(new Validator_Length(0, 64), new Validator_Regex('#^[^"]*$#')) );
				$form->addElement( 'host', 'localhost', t('SQL host'), new Validator_Length(1, 80) );
				$form->addElement( 'port', 3306, t('SQL Port'), new Validator_Int );
				$form->addElement( 'dbname', null, t('SQL Database'), new Validator_Length(1, 64) );
				$form->addElement( 'prefix', 'tcm_', t('Table prefix'), array(new Validator_Length(0, 12), new Validator_Alphanumeric('_-')) );
				if ( $form->hasInput() && $form->isValid() ) {
					$data = $form->getValues();
					$data['type'] = 'mysql';
				} else {
					return $form->getOutput();
				}
			}
			try {
				$sql = new SQL( $data['type'], $data['dbname'], $data['host'], $data['user'], $data['pass'], $data['port'] );
				$sql->setPrefix( $data['prefix'] );
				$sql->query( "SET NAMES 'utf8'" ); # Use UTF-8 character set for the connection
				$sql->loadSqlFile( $this->getPath().'/schema.sql' );
				/**
				* Update config.ini.php file with the new values
				*/
				$confKeys = array(
								'sql/enable'	=> true,
								'sql/host'		=> $data['host'],
								'sql/user'		=> $data['user'],
								'sql/pass'		=> $data['pass'],
								'sql/database'	=> $data['dbname'],
								'sql/type'		=> $data['type'],
								'sql/prefix'	=> $data['prefix'],
								'sql/port'		=> $data['port'],
								'hashing/salt'	=> zula_make_salt(),
								'acl/enable'	=> true
								);
				if ( $this->_input->has( 'get', 'ns' ) ) {
					$confKeys['url_router/type'] = 'standard';
				}
				$this->_config_ini->update( array_keys($confKeys), array_values($confKeys) );
				try {
					$this->_config_ini->writeIni();
					if ( isset( $_SESSION['installStage'] ) ) {
						++$_SESSION['installStage'];
					}
					$this->_event->success( t('Initial database tables have been created') );
					return zula_redirect( $this->_router->makeUrl('install', 'user') );
				} catch ( Config_ini_FileNotWriteable $e ) {
					$this->_event->error( $e->getMessage() );
				}
			} catch ( SQL_UnableToConnect $e ) {
				$this->_event->error( t('Unable to connect to, or select SQL database') );
			}
			if ( isset( $form ) ) {
				return $form->getOutput();
			} else {
				$this->_zula->setExitCode( 3 );
				return false;
			}
		}

	}

?>
