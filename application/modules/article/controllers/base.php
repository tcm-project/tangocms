<?php

/**
 * Zula Framework Module
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Article
 */

	abstract class ArticleBase extends Zula_ControllerBase {

		/**
		 * Takes an ID, or by default the 'article/meta_format' value and
		 * returns the correct language string to use
		 *
		 * @param int $id
		 * @return string
		 */
		public function getMetaFormat( $id=null ) {
			if ( !$id ) {
				$id = $this->_config->get( 'article/meta_format' );
			}
			switch( $id ) {
				case 0:
					return t('Published %1$s by %2$s in %3$s');
				case 1:
					return t('Published by %2$s, %1$s');
				case 2:
					return t('Posted %1$s by %2$s');
				case 3:
					return t('%1$s by %2$s');
				case 4:
					return '%1$s';
				case 5:
				default:
					return '';
			}
		}

	}

?>