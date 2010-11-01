<?php

/**
 * Zula Framework Module
 * Manages RSS Feeds
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Robert Clipsham
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, 2009, 2010 Robert Clipsham
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Rss
 */

	class Rss_controller_feed extends Zula_ControllerBase {

		/**
		 * The directory for RSS feeds
		 * @var string
		 */
		protected $rssDir = '';

		/**
		 * Constructor
		 * Calls the parent constructor to register the methods
		 *
		 * @return object
		 */
		public function __construct( $moduleDetails, $config, $sector ) {
			parent::__construct( $moduleDetails, $config, $sector );
			$this->rssDir = $this->_zula->getDir( 'tmp' ).'/rss';
			$this->_session->storePrevious( false ); # Don't store previous URL in session
		}

		/**
		 * Magic method - allows for shorter URL's eg:
		 * /rss/view/articles-latest
		 *
		 * @param string $name
		 * @param array $args
		 * @return string
		 */
		public function __call( $name, $args ) {
			$file = $this->rssDir.'/'.strtolower( substr($name, 0, -7) ).'.xml';
			if ( preg_match( '#[^A-Z0-9\-_]+#i', $name ) || !file_exists( $file ) ) {
				throw new Module_ControllerNoExist( 'The RSS feed requested does not exist!' );
			}
			header( 'Content-Type: application/rss+xml; charset=utf-8' );
			readfile( $file );
			return false;
		}

	}

?>
