<?php

/**
 * Zula Framework Module (settings)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
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
								'date'		=> array(
													'FORMAT'		=> 'date/format',
													'RELATIVE'		=> 'date/use_relative',
													'TIMEZONE'		=> 'date/timezone',
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
										t('E-Mail')			=> $this->_router->makeUrl( 'settings', 'email' ),
										t('Date & Time')	=> $this->_router->makeUrl( 'settings', 'date' ),
										t('Server & Security') => $this->_router->makeUrl( 'settings', 'security' ),
										t('Cache & Performance')=> $this->_router->makeUrl( 'settings', 'cache' ),
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
			$this->_i18n->textDomain( $this->textDomain() );
			$this->setTitle( t('Settings') );
			$this->setOutputType( self::_OT_CONFIG );
			$name = substr( $name, 0, -7 );
			// Display correct view
			switch( $name ) {
				case 'index':
					$name = 'general';
				case 'general':
					$this->setTitle( t('General Settings') );
					$view = $this->loadView( 'general.html' );
					break;

				case 'email':
					$this->setTitle( t('E-Mail Settings') );
					$view = $this->loadView( 'email.html' );
					break;

				case 'date':
					$this->setTitle( t('Date & Time Settings') );
					$view = $this->loadView( 'date.html' );
					break;

				case 'security':
					$this->setTitle( t('Server & Security Settings') );
					$view = $this->loadView( 'security.html' );
					break;

				case 'cache':
					$this->setTitle( t('Cache & Performance Settings') );
					$view = $this->loadView( 'cache.html' );
					break;

				case 'editing':
					$this->setTitle( t('Editing Settings') );
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
			if ( !trim( $this->config['date']['TIMEZONE'] ) ) {
				$this->config['date']['TIMEZONE'] = date_default_timezone_get();
			}
			$view->assign( array('CONFIG' => $this->config[ $name ]) );
			$view->assignHtml( array('CSRF'	=> $this->_input->createToken( true )) );
			return $view->getOutput();
		}

	}

?>
