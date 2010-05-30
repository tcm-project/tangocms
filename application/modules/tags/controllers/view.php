<?php

/**
 * Zula Framework Module (Tags)
 * --- View content associated with tags
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Robert Clipsham
 * @copyright Copyright (C) 2009, Robert Clipsham
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Tags
 */

	class Tags_controller_view extends Zula_ControllerBase {

		/**
		 * Magic method - Allows for shorter urls eg tags/view/foobar
		 *
		 * @param string $name
		 * @param array $args
		 * @return string
		 */
		public function __call( $name, $args ) {
			$tagName = substr( $name, 0, -7 );
			$this->setTitle( sprintf( t('Content Tagged "%1$s"'), $tagName ) );
			$this->setOutputType( self::_OT_CONTENT_INDEX );
			// Display the view
			$view = $this->loadView( 'view.html' );
			$view->assign( array(
							'URLS'	=> $this->_model()->getUrls( $tagName ),
							'TAG'	=> $tagName
						 ));
			return $view->getOutput();
		}

	}

?>
