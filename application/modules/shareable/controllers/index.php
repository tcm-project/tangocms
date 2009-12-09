<?php
// $Id: index.php 2823 2009-12-03 15:12:40Z alexc $

/**
 * Zula Framework Module (shareable)
 * --- Adds in buttons to social news sites such as Digg and Reddit.
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Shareable
 */

	class Shareable_controller_index extends Zula_ControllerBase {

		/**
		 * Shows all shareable sites
		 *
		 * @return string
		 */
		public function indexSection() {
			$sites = $this->_model( 'shareable', 'shareable' )->getSites( Shareable_Model::_ENABLED );
			if ( $this->_dispatcher->isDispatched() && $this->_dispatcher->getStatusCode() == 200 && $sites ) {
				$view = new View( 'main.html', 'shareable' );
				$view->assign( array(
									'SITES'		=> $sites,
									'TITLE'		=> $this->_dispatcher->getReqCntrl()->getDetail('title'),
									'SECTOR'	=> $this->getSector(),
									));
				return $view->getOutput();
			} else {
				return $this->inSector('SC') ? '' : false;
			}
		}

	}

?>
