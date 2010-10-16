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

	class Upgrade_controller_migrate extends Zula_ControllerBase {

		/**
		 * The update routes (ie, you must upgrade to foo before going to bar)
		 * @var array
		 */
		protected $routes = array(
								# Stable Releases
								'2.5.5'			=> '2.6.0-alpha1',
								'2.5.4'			=> '2.5.5',
								'2.5.3'			=> '2.5.4',
								'2.5.2'			=> '2.5.3',
								'2.5.1'			=> '2.5.2',
								'2.5.0'			=> '2.5.1',

								'2.4.0'			=> '2.5.0-alpha1',

								'2.3.3'			=> '2.4.0-alpha1',
								'2.3.2'			=> '2.3.3',
								'2.3.1'			=> '2.3.2',
								'2.3.0'			=> '2.3.1',

								# Dev Releases
								'2.5.64'		=> '2.6.0-beta1',
								'2.5.63'		=> '2.6.0-beta1',
								'2.5.62'		=> '2.6.0-beta1',
								'2.5.61'		=> '2.6.0-beta1',
								'2.5.60'		=> '2.6.0-beta1',

								'2.5.56'		=> '2.6.0-alpha1',
								'2.5.55'		=> '2.6.0-alpha1',
								'2.5.54'		=> '2.6.0-alpha1',
								'2.5.53'		=> '2.6.0-alpha1',
								'2.5.52'		=> '2.6.0-alpha1',
								'2.5.51'		=> '2.6.0-alpha1',
								'2.5.50'		=> '2.6.0-alpha1',

								'2.4.90'		=> '2.5.0',

								'2.4.55'		=> '2.5.0-rc1',

								'2.4.54'		=> '2.5.0-alpha1',
								'2.4.53'		=> '2.5.0-alpha1',
								'2.4.52'		=> '2.5.0-alpha1',
								'2.4.51'		=> '2.5.0-alpha1',
								'2.4.50'		=> '2.5.0-alpha1',

								'2.3.90'		=> '2.4.0',

								'2.3.81'		=> '2.4.0-rc1',
								'2.3.80'		=> '2.4.0-rc1',

								'2.3.73'		=> '2.4.0-beta1',
								'2.3.72'		=> '2.4.0-beta1',
								'2.3.71'		=> '2.4.0-beta1',
								'2.3.70'		=> '2.4.0-beta1',

								'2.3.64'		=> '2.4.0-alpha2',
								'2.3.63'		=> '2.4.0-alpha2',
								'2.3.62'		=> '2.4.0-alpha2',
								'2.3.61'		=> '2.4.0-alpha2',
								'2.3.60'		=> '2.4.0-alpha2',

								'2.3.56'		=> '2.4.0-alpha1',
								'2.3.55'		=> '2.4.0-alpha1',
								'2.3.54'		=> '2.4.0-alpha1',
								'2.3.53'		=> '2.4.0-alpha1',
								'2.3.52'		=> '2.4.0-alpha1',
								'2.3.51'		=> '2.4.0-alpha1',
								'2.3.50'		=> '2.4.0-alpha1',
								);

		/**
		 * Current version the upgrader has upgraded to
		 * @var string
		 */
		protected $version = _PROJECT_VERSION;

		/**
		 * Constructor
		 *
		 * @return object
		 */
		public function __construct( $moduleDetails, $config, $sector ) {
			parent::__construct( $moduleDetails, $config, $sector );
			$this->_config->update( 'config/title',  sprintf('%s %s upgrader', _PROJECT_NAME, _PROJECT_LATEST_VERSION) );
		}

		/**
		 * Does all that is needed for upgrading to a new version
		 *
		 * @return string
		 */
		public function indexSection() {
			$this->setTitle( 'Upgrading' );
			if (
				$this->_zula->getMode() != 'cli' &&
				(!isset( $_SESSION['upgradeStage'] ) || $_SESSION['upgradeStage'] !== 4)
			) {
				return zula_redirect( $this->_router->makeUrl('upgrade', 'version') );
			}
			if ( !isset( $this->routes[ _PROJECT_VERSION ] ) ) {
				// Eek, this isn't suppose to happen
				$langStr = t('Version %s is not supported by this upgrader');
				$this->_event->error( sprintf( $langStr, _PROJECT_VERSION ) );
				if ( $this->_zula->getMode() == 'cli' ) {
					exit( 3 );
				} else {
					return zula_redirect( $this->_router->makeUrl('index') );
				}
			}
			/**
			 * Run the correct upgrade route, storing the current version
			 * that the upgrade has successfully upgraded to.
			 */
			$success = true;
			while( isset( $this->routes[ $this->version ] ) ) {
				$upgradeRoute = $this->routes[ $this->version ];
				if ( !is_array( $upgradeRoute ) ) {
					$upgradeRoute = array( $upgradeRoute );
				}
				foreach( $upgradeRoute as $upgradeTo ) {
					$method = 'upgradeTo_'.preg_replace( '#[^A-Z0-9_]+#i', '', str_replace('-', '_', $upgradeTo) );
					if ( method_exists( $this, $method ) ) {
						$tmpVersion = $this->$method();
						if ( $tmpVersion === false ) {
							$success = false;
							$this->_event->error( sprintf( t('Failed to upgrade from "%1$s" to "%2$s"'), $this->version, $upgradeTo ) );
							break;
						} else if ( $tmpVersion === true ) {
							$this->version = $upgradeTo;
						} else {
							$this->version = $tmpVersion;
						}
					} else {
						$this->version = $upgradeTo;
					}
					$this->_config_ini->update( 'config/version', $this->version );
				}
			}
			$_SESSION['project_version'] = $this->version;
			// Remove all unused ACL rules and rewrite the config.ini.php file
			$this->_acl->cleanRules();
			try {
				$this->_config_ini->writeIni();
			} catch ( Config_ini_FileNotWriteable $e ) {
				$this->_event->error( sprintf( t('Configuration file "%s" is not writable'), $this->_config_ini->getFile() ) );
			}
			// Check if upgrade was (fully) successful
			if ( $success ) {
				$this->_event->success( sprintf( t('Successfully upgraded from "%1$s" to "%2$s"'), _PROJECT_VERSION, $this->version ) );
			} else {
				$langStr = t('A partial upgrade from "%s" to "%s" (latest %s) occured');
				$this->_event->error( sprintf( $langStr, _PROJECT_VERSION, $this->version, _PROJECT_LATEST_VERSION ) );
			}
			if ( $this->_zula->getMode() == 'cli' ) {
				return true;
			} else {
				++$_SESSION['upgradeStage'];
				return zula_redirect( $this->_router->makeUrl('upgrade', 'complete') );
			}
		}

		/**
		 * Attempts to run multiple SQL files
		 *
		 * @param array $files
		 * @return int
		 */
		protected function sqlFile( $files ) {
			if ( !is_array( $files ) ) {
				$files = array( $files );
			}
			$path = $this->getPath().'/sql';
			$i = 0;
			foreach( $files as $file ) {
				try {
					$this->_sql->loadSqlFile( $path.'/'.$file );
					++$i;
				} catch ( Sql_QueryFailed $e ) {
					// Handle module tables failing differently
					if ( strpos( $e->getMessage(), '42S02' ) !== false && preg_match( "#'.*?\..*?mod_.*?'#i", $e->getMessage() ) ) {
						$this->_log->message( 'Upgrader: '.$e->getMessage(), Log::L_NOTICE );
					} else {
						throw $e;
					}
				}
			}
			return $i;
		}

		/**
		 * Upgrades to 2.4.0-alpha1 (2.3.60)
		 *
		 * @return bool|string
		 */
		protected function upgradeTo_240_alpha1() {
			switch( $this->version ) {
				case '2.3.1':
				case '2.3.2':
				case '2.3.3':
				case '2.3.50':
					foreach( Layout::getAll() as $layout ) {
						$layoutObj = new Layout( $layout['name'] );
						foreach( $layoutObj->getControllers() as $cntrlr ) {
							if ( empty( $cntrlr['config']['force_title'] ) ) {
								if ( isset( $cntrlr['config']['display_title'] ) ) {
									$displayTitle = (bool) $cntrlr['config']['display_title'];
								} else {
									$displayTitle = true;
								}
								$cntrlr['config']['displayTitle'] = zula_bool2str( $displayTitle );
							} else {
								$cntrlr['config']['displayTitle'] = 'custom';
								$cntrlr['config']['customTitle'] = $cntrlr['config']['force_title'];
							}
							unset( $cntrlr['config']['display_title'], $cntrlr['config']['force_title'] );
							$layoutObj->editController( $cntrlr['id'], $cntrlr );
						}
						$layoutObj->save();
					}
				case '2.3.51':
					$this->sqlFile( '2.4.0-alpha1/2.3.52.sql' );
				case '2.3.52':
					$this->sqlFile( '2.4.0-alpha1/2.3.53.sql' );
					foreach( Layout::getAll() as $layout ) {
						$layoutObj = new Layout( $layout['name'] );
						foreach( $layoutObj->getControllers() as $cntrlr ) {
							if ( $cntrlr['mod'] == 'poll' ) {
								$cntrlr['con'] = 'view';
								if ( empty( $cntrlr['config']['display_poll'] ) ) {
									$cntrlr['sec'] = 'index';
								} else if ( $cntrlr['config']['display_poll'] == 'random' ) {
									$cntrlr['sec'] = 'random';
								} else {
									$cntrlr['sec'] = $cntrlr['config']['display_poll'];
								}
								unset( $cntrlr['config']['display_poll'] );
								$layoutObj->editController( $cntrlr['id'], $cntrlr );
							}
						}
						$layoutObj->save();
					}
				case '2.3.53':
					$this->sqlFile( '2.4.0-alpha1/2.3.54.sql' );
				case '2.3.54':
					$this->sqlFile( '2.4.0-alpha1/2.3.55.sql' );
				case '2.3.55':
					$this->sqlFile( '2.4.0-alpha1/2.3.56.sql' );
				case '2.3.56':
				default:
					return '2.3.60';
			}
		}

		/**
		 * Upgrades to 2.4.0-alpha2 (2.3.70)
		 *
		 * @return bool|string
		 */
		protected function upgradeTo_240_alpha2() {
			switch( $this->version ) {
				case '2.3.60':
					$this->sqlFile( '2.4.0-alpha2/2.3.61.sql' );
				case '2.3.61':
					// Captcha/Antispam changes (feature #149)
					$this->_config_sql->add( array('antispam/recaptcha/public', 'antispam/recaptcha/private'), array('', '') );
					$this->_config_sql->add( 'antispam/backend',
											 $this->_config->get('captcha/use') ? 'captcha' : 'disabled'
										   );
					$this->_config_sql->delete( 'captcha/use' );
				case '2.3.62':
					$this->_config_ini->add( 'cache/google_cdn', '1' );
				case '2.3.63':
					$this->sqlFile( '2.4.0-alpha2/2.3.64.sql' );
					$this->_config_sql->delete( array(
													'media/border_width', 'media/border_color',
													'media/add_copyright', 'media/copyright_text',
													'media/enable_quickjump', 'media/quickjump_amount'
													));
				case '2.3.64':
					$this->_config_sql->delete( 'tcm/codename' );
					return '2.3.70';
				default:
					return '2.3.70';
			}
		}

		/**
		 * Upgrades to 2.4.0-beta1 (2.3.80)
		 *
		 * @return bool|string
		 */
		protected function upgradeTo_240_beta1() {
			switch( $this->version ) {
				case '2.3.70':
					$this->sqlFile( '2.4.0-beta1/2.3.71.sql' );
					foreach( Layout::getAll() as $layout ) {
						$layoutObj = new Layout( $layout['name'] );
						foreach( $layoutObj->getControllers() as $cntrlr ) {
							if ( $cntrlr['mod'] == 'media' && $cntrlr['con'] == 'cat' ) {
								$cntrlr['con'] = 'index';
								$cntrlr['config']['display_cat'] = $cntrlr['sec'];
								$cntrlr['sec'] = 'cat';
								$layoutObj->editController( $cntrlr['id'], $cntrlr );
							}
						}
						$layoutObj->save();
					}
				case '2.3.71':
					$this->_config_sql->delete( 'media/number_latest' );
				case '2.3.72':
					$this->_config_sql->add( 'media/use_lightbox', true );
				case '2.3.73':
					return '2.3.80';
				default:
					return '2.3.80';
			}
		}

		/**
		 * Upgrades to 2.4.0-rc1 (2.3.90)
		 *
		 * @return bool|string
		 */
		protected function upgradeTo_240_rc1() {
			switch( $this->version ) {
				case '2.3.80':
					foreach( Layout::getAll() as $layout ) {
						$layoutObj = new Layout( $layout['name'] );
						foreach( $layoutObj->getControllers() as $cntrlr ) {
							if ( $cntrlr['mod'] == 'media' && $cntrlr['con'] == 'index' && $cntrlr['sec'] == 'latest' ) {
								$cntrlr['sec'] = 'cat';
								$cntrlr['config']['display_cat'] = '';
								$layoutObj->editController( $cntrlr['id'], $cntrlr );
							}
						}
						$layoutObj->save();
					}
				case '2.3.81':
					return '2.3.90';
				default:
					return '2.3.90';
			}
		}

		/**
		 * Upgrades to 2.5.0-alpha1 (2.4.60)
		 *
		 * @return bool|string
		 */
		protected function upgradeTo_250_alpha1() {
			switch( $this->version ) {
				case '2.4.0':
				case '2.4.50':
					$this->sqlFile( '2.5.0-alpha1/2.4.51.sql' );
				case '2.4.51':
					$this->sqlFile( '2.5.0-alpha1/2.4.52.sql' );
				case '2.4.52':
					$this->sqlFile( '2.5.0-alpha1/2.4.53.sql' );
				case '2.4.53':
					$this->sqlFile( '2.5.0-alpha1/2.4.54.sql' );
				case '2.4.54':
					$this->sqlFile( '2.5.0-alpha1/2.4.55.sql' );
					$this->_config_sql->add( 'session/expire_pw', '0' );
					return '2.4.55';
				default:
					return '2.4.60';
			}
		}

		/**
		 * Upgrades to 2.5.0-rc1 (2.4.90)
		 *
		 * @return bool|string
		 */
		protected function upgradeTo_250_rc1() {
			switch( $this->version ) {
				case '2.4.55':
				default:
					return '2.4.90';
			}
		}

		/**
		 * Upgrades to 2.5.1
		 *
		 * @return bool|string
		 */
		protected function upgradeTo_251() {
			switch( $this->version ) {
				case '2.5.0':
					$this->sqlFile( '2.5.1/2.5.1.sql' );
				default:
					return '2.5.1';
			}
		}

		/**
		 * Upgrades to 2.6.0-alpha1 (2.5.60)
		 *
		 * @return bool|string
		 */
		protected function upgradeTo_260_alpha1() {
			switch( $this->version ) {
				case '2.5.0':
				case '2.5.1':
				case '2.5.2':
				case '2.5.3':
				case '2.5.4':
				case '2.5.5':
				case '2.5.50':
					foreach( array('main', 'admin') as $siteType ) {
						$layout = new Layout( $siteType.'-default' );
						$sc = $layout->getControllers( 'SC' );
						$sc = array_shift( $sc );
						$layout->detachController( $sc['id'] );
						$layout->save();
						// Create the new FPSC (FrontPage Sector Content) layout
						$layout = new Layout( 'fpsc-'.$siteType );
						$layout->addController( 'SC', $sc, $sc['id'] );
						$layout->save();
					}
				case '2.5.51':
					$this->sqlFile( '2.6.0-alpha1/2.5.52.sql' );
				case '2.5.52':
					$this->sqlFile( '2.6.0-alpha1/2.5.53.sql' );
				case '2.5.53':
					$this->_config_sql->add( 'media/wm_position', 'bl' );
				case '2.5.54':
					/**
					 * Update the ACL resources for the page changes (#247)
					 */
					$this->sqlFile( '2.6.0-alpha1/2.5.55.sql' );
					$addRoles = $editRoles = $manageRoles = array();
					foreach( $this->_acl->getAllRoles() as $role ) {
						if ( $this->_acl->check( 'page_delete', $role['id'] ) ) {
							$editRoles[] = $role['name'];
							$manageRoles[] = $role['name'];
						} else if ( $this->_acl->check( 'page_edit', $role['id'] ) ) {
							$editRoles[] = $role['name'];
						}
						if ( $this->_acl->check( 'page_add', $role['id'] ) ) {
							$addRoles[] = $role['name'];
						}
					}
					// Add in the new resources
					$query = $this->_sql->query( 'SELECT SUBSTRING(name, 11) AS pid FROM {SQL_PREFIX}acl_resources
													WHERE name LIKE "page-view_%"' );
					foreach( $query->fetchAll( PDO::FETCH_COLUMN ) as $pid ) {
						$this->_acl->allowOnly( 'page-edit_'.$pid, $editRoles );
						$this->_acl->allowOnly( 'page-manage_'.$pid, $manageRoles );
					}
					$this->_acl->deleteResource( array('page_add', 'page_edit', 'page_delete') );
					$this->_acl->allowOnly( 'page_manage', $addRoles );
				case '2.5.55':
					$this->sqlFile( '2.6.0-alpha1/2.5.56.sql' );
				case '2.5.56':
				default:
					return '2.5.60';
			}
		}

		/**
		 * Upgrades to 2.6.0-beta1 (2.5.80)
		 *
		 * @return bool|string
		 */
		protected function upgradeTo_260_beta1() {
			switch( $this->version ) {
				case '2.5.60':
					$this->_config_sql->add( 'article/meta_format', 0 );
				case '2.5.61':
					$this->_config_sql->add( 'article/max_display_age', 145152000 );
				case '2.5.62':
					$this->sqlFile( '2.6.0-beta1/2.5.63.sql' );
				case '2.5.63':
					$this->sqlFile( '2.6.0-beta1/2.5.64.sql' );
				case '2.5.64':
					$this->sqlFile( '2.6.0-beta1/2.5.65.sql' );
					return '2.5.65';
				default:
					return '2.5.80';
			}
		}

	}

?>
