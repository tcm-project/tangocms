<?php

/**
 * Zula Framework Module (shareable)
 * --- Adds in buttons to social news sites such as Digg and Reddit.
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Shareable
 */

	class Shareable_controller_config extends Zula_ControllerBase {

		/**
		 * Shows a list of enabled and disabled sites
		 *
		 * @return string
		 */
		public function indexSection() {
			if ( !$this->_acl->check( 'shareable_manage' ) ) {
				throw new Module_NoPermission;
			}
			$this->_i18n->textDomain( $this->textDomain() );
			$this->setTitle( t('Shareable Configuration') );
			$this->setOutputType( self::_OT_CONFIG );
			// Get sites
			$sites = $this->_model()->getSites();
			$siteCount = count( $sites );
			// Build form with validation
			$form = new View_form( 'config/manage.html', 'shareable' );
			$form->action( $this->_router->makeUrl( 'shareable', 'config' ) );
			$form->addElement( 'shareable/order', null, t('Order'), new Validator_Length( $siteCount ) );
			$form->addElement( 'shareable/enabled', null, t('Enabled'), new Validator_Length(0, $siteCount), false );
			if ( $form->hasInput() && $form->isValid() ) {
				try {
					$enabledSites = $form->getValues( 'shareable/enabled' );
				} catch ( View_FormValueNoExist $e ) {
					$enabledSites = array();
				}
				$editData = array();
				foreach( $form->getValues( 'shareable/order' ) as $key=>$val ) {
					$editData[ $key ] = array(
											'order'		=> abs( $val ),
											'disabled'	=> !in_array( $key, $enabledSites ),
											);
				}
				$this->_model()->edit( $editData );
				$this->_event->success( t('Updated Config') );
				return zula_redirect( $this->_router->makeUrl( 'shareable', 'config' ) );
			}
			$this->_theme->addJsFile( 'jQuery/plugins/dnd.js' );
			$this->addAsset( 'js/dnd_order.js' );
			$form->assign( array('SITES' => $sites) );
			return $form->getOutput();
		}

	}

?>
