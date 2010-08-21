<?php

/**
 * Zula Framework Module (settings)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Settings
 */

	class Settings_controller_index extends Zula_ControllerBase {

		/**
		 * Holds which config items should be available
		 * to which 'category'
		 * @var array
		 */
		protected $config = array(
								'general'	=> array(
													'TITLE'			=> 'config/title',
													'TITLE_FORMAT'	=> 'config/title_format',
													'SLOGAN'		=> 'config/slogan',
													'KEYWORDS'		=> 'meta/keywords',
													'DESCRIPTION'	=> 'meta/description',
													),
								'email'		=> array(
													'OUTGOING'		=> 'mail/outgoing',
													'INCOMING'		=> 'mail/incoming',
													'PREFIX'		=> 'mail/subject_prefix',
													'TYPE'			=> 'mail/type',
													'SIGNATURE'		=> 'mail/signature',
													'SMTP_HOST'		=> 'mail/smtp_host',
													'SMTP_PORT'		=> 'mail/smtp_port',
													'SMTP_USERNAME'	=> 'mail/smtp_username',
													'SMTP_PASSWORD'	=> 'mail/smtp_password',
													'SMTP_ENCRYPTION'=> 'mail/smtp_encryption',
													),
								'locale'	=> array(
													'DATE_FORMAT'	=> 'date/format',
													'DATE_RELATIVE'	=> 'date/use_relative',
													'DATE_TIMEZONE'	=> 'date/timezone',
													'I18N_LANG'		=> 'locale/default',
													'I18N_ENGINE'	=> 'locale/engine',
													),
								'security'	=> array(
													'PROTOCOL'		=> 'config/protocol',
													'ANTI_BACKEND'	=> 'antispam/backend',
													'RECAP_PUB'		=> 'antispam/recaptcha/public',
													'RECAP_PRI'		=> 'antispam/recaptcha/private',
													),
								'cache'		=> array(
													'TYPE'			=> 'cache/type',
													'TTL'			=> 'cache/ttl',
													'JS_AGGREGATE'	=> 'cache/js_aggregate',
													'GOOGLE_CDN'	=> 'cache/google_cdn',
													),
								'editing'	=> array(
													'DEFAULT'		=> 'editor/default',
													'PARSE_PHP'		=> 'editor/parse_php',
													),
								);

		/**
		 * Constructor
		 *
		 * Sets the page links to those of the top-level categories
		 */
		public function __construct( $moduleDetails, $config, $sector ) {
			parent::__construct( $moduleDetails, $config, $sector );
			$this->setPageLinks( array(
										t('General')		=> $this->_router->makeUrl( 'settings', 'general' ),
										t('Email')			=> $this->_router->makeUrl( 'settings', 'email' ),
										t('Locale')			=> $this->_router->makeUrl( 'settings', 'locale' ),
										t('Server & security') => $this->_router->makeUrl( 'settings', 'security' ),
										t('Cache & performance')=> $this->_router->makeUrl( 'settings', 'cache' ),
										t('Editing')		=> $this->_router->makeUrl( 'settings', 'editing' ),
										));
		}

		/**
		 * Magic 'call' method to provide the nicer URLs
		 * and make things automated
		 *
		 * @param string $name
		 * @param array $args
		 * @return strng
		 */
		public function __call( $name, $args ) {
			$this->setTitle( t('Settings') );
			$this->setOutputType( self::_OT_CONFIG );
			$name = substr( $name, 0, -7 );
			// Display correct view
			switch( $name ) {
				case 'index':
					$name = 'general';
				case 'general':
					$this->setTitle( t('General settings') );
					$view = $this->loadView( 'general.html' );
					break;

				case 'email':
					$this->setTitle( t('Email settings') );
					$view = $this->loadView( 'email.html' );
					break;

				case 'locale':
					$this->setTitle( t('Locale settings') );
					$view = $this->loadView( 'locale.html' );
					/**
					 * Get all available layouts that can be installed
					 */
					$availableLocales = $this->_i18n->getAvailableLangs();
					if ( ($installable = $this->_cache->get('settings_installable_i18n')) === false ) {
						$installable = null;
						if ( ini_get( 'allow_url_fopen' ) ) {
							$version = str_replace( '-', '/', zula_version_map(_PROJECT_VERSION) );
							$json = @file_get_contents( 'http://releases.tangocms.org/'.$version.'/i18n/locales.json' );
							if ( isset( $http_response_header[0] ) && strpos( $http_response_header[0], 200 ) !== false ) {
								// Only show langs that are not already 'installed'
								$installable = array_diff_key( json_decode($json, true), $availableLocales );
							}
						}
						if ( $installable === null ) {
							$this->_log->message( 'failed to get list of locales, check "allow_url_fopen"', Log::L_WARNING );
						} else {
							$this->_cache->add( 'settings_installable_i18n', $installable );
						}
					}
					$view->assign( array(
										'LOCALES'		=> $availableLocales,
										'INSTALLABLE'	=> (array) $installable,
										));
					break;

				case 'security':
					$this->setTitle( t('Server & security settings') );
					$view = $this->loadView( 'security.html' );
					break;

				case 'cache':
					$this->setTitle( t('Cache & performance settings') );
					$view = $this->loadView( 'cache.html' );
					break;

				case 'editing':
					$this->setTitle( t('Editing settings') );
					$view = $this->loadView( 'editing.html' );
					break;

				default:
					throw new Module_ControllerNoExist;
			}
			// Assign the settings/config items/values
			foreach( $this->config[ $name ] as &$val ) {
				try {
					$val = $this->_config->get( $val );
				} catch ( Config_KeyNoExist $e ) {
					$val = '';
				}
			}
			if ( !trim( $this->config['locale']['DATE_TIMEZONE'] ) ) {
				$this->config['locale']['DATE_TIMEZONE'] = date_default_timezone_get();
			}
			$view->assign( array('CONFIG' => $this->config[ $name ]) );
			$view->assignHtml( array('CSRF'	=> $this->_input->createToken( true )) );
			return $view->getOutput();
		}

	}

?>
