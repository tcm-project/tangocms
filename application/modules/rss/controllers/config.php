<?php
/*
 * RSS Config (Rss)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Robert Clipsham
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, Robert Clipsham
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Rss
 */

	class Rss_controller_config extends Zula_ControllerBase {

		/**
		 * All RSS feed file names
		 * @var array
		 */
		protected $feeds = array();

		/**
		 * Constructor function
		 * --- Gathers all the feeds
		 *
		 * @return object
		 */
		public function __construct( $moduleDetails, $config, $sector ) {
			parent::__construct( $moduleDetails, $config, $sector );
			$rssDir = $this->_zula->getDir( 'tmp' ).'/rss';
			$dirLen = strlen( $rssDir )+1;
			$feeds = glob( $rssDir.'/*.xml' );
			if ( !empty( $feeds ) ) {
				foreach( $feeds as $file ) {
					$name = substr( $file, $dirLen, -4 );
					$this->feeds[ $name ] = ucfirst( $name );
				}
			}
		}

		/**
		 * Display some RSS options, and a list of the current feeds
		 *
		 * @return string
		 */
		public function indexSection() {
			if ( !$this->_acl->check( 'rss_edit' ) ) {
				throw new Module_NoPermission();
			}
			$this->setTitle( t('RSS Configuration') );
			$this->setOutputType( self::_OT_CONFIG );
			/**
			 * Prepare form validation
			 */
			$form = new View_Form( 'config/main.html', 'rss' );
			$form->addElement( 'rss/global', (bool) $this->_config->get('rss/global_agg_enable'), t('Global Aggregation'), new Validator_Bool );
			$form->addElement( 'rss/default', $this->_config->get('rss/default_feed'), t('Default Feed'), new Validator_Alphanumeric('_-') );
			$form->addElement( 'rss/items', $this->_config->get('rss/items_per_feed'), t('Number of Items'), new Validator_Numeric );
			if ( $form->hasInput() && $form->isValid() ) {
				$fd = $form->getValues( 'rss' );
				// Check default feed given is valid
				if ( $fd['default'] != 0 && !in_array( $fd['default'], array_keys($this->feeds) ) ) {
					$this->_event->error( t('Please select a valid default RSS feed.') );
				} else {
					$this->_config_sql->update( array('rss/global_agg_enable', 'rss/items_per_feed', 'rss/default_feed'),
												array($fd['global'], $fd['items'], $fd['default']) );
					$this->_event->success( t('Updated RSS configuration') );
					return zula_redirect( $this->_router->makeUrl( 'rss', 'config' ) );
				}
			}
			// Add additional data
			$form->assign( array('RSS_FEEDS' => $this->feeds) );
			return $form->getOutput();
		}

	}

?>
