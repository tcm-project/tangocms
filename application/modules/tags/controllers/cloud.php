<?php

/**
 * Zula Framework Module (Tags)
 * --- Allows for a tag cloud
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Robert Clipsham
 * @copyright Copyright (C) 2009, Robert Clipsham
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Tags
 */

	class Tags_controller_cloud extends Zula_ControllerBase {

		/**
		 * Display a tag cloud
		 *
		 * @return string
		 */
		public function indexSection() {
			$this->_locale->textDomain( $this->textDomain() );
			$this->setTitle( t('Tag Cloud') );
			$view = $this->loadView( 'cloud.html' );
			$view->assign( array('CLOUD' => $this->createCloud()) );
			return $view->getOutput();
		}

		/**
		 * Create a tag cloud from the tags
		 *
		 * @todo Make it configurable using the config controller
		 * @return string
		 */
		protected function createCloud() {
			$stats = $this->_model()->getStats();
			arsort( $stats );
			$ret = array();
			$i = 1;
			foreach( $stats as $tag=>$freq ) {
				$size = $i <= 12 ? (string) $i : 'normal';
				$ret[ $tag ] = 'tag-link-'.$size;
				$i++;
			}
			ksort( $ret );
			return $ret;
		}

	}

?>
