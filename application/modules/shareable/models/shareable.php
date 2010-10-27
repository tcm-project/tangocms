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

	class Shareable_model extends Zula_ModelBase {

		/**
		 * Constants used for filtering sites
		 */
		const
				_ENABLED	= 1,
				_DISABLED	= 2,
				_ALL		= 3;

		/**
		 * Gets share this sites
		 *
		 * @param int $type	Enabled, Disabled or All
		 * @return array
		 */
		public function getSites( $type=self::_ALL ) {
			if ( $type == self::_ENABLED ) {
				$cacheKey = 'shareable_enabled';
				$where = 'WHERE disabled = 0';
			} else if ( $type == self::_DISABLED ) {
				$cacheKey = 'shareable_disabled';
				$where = 'WHERE disabled = 1';
			} else {
				$cacheKey = 'shareable_all';
				$where = null;
			}
			if ( ($sites = $this->_cache->get( $cacheKey )) === false ) {
				$sites = $this->_sql->query( 'SELECT * FROM {PREFIX}mod_shareable '.$where.'
											  ORDER by disabled ASC, `order` ASC, name ASC' )->fetchAll( PDO::FETCH_ASSOC );
				$this->_cache->add( $cacheKey, $sites );
			}
			return $sites;
		}

		/**
		 * Edits a single, or multiple sites (order/disabled)
		 *
		 * @param mixed $id
		 * @param bool $enabled
		 * @param int $order
		 * @return int
		 */
		public function edit( $id, $enabled=true, $order=0 ) {
			if ( !is_array( $id ) ) {
				$id = array( $id => array('order' => $order, 'disabled' => !$enabled) );
			}
			$pdoSt = $this->_sql->prepare( 'UPDATE {PREFIX}mod_shareable SET disabled = ?, `order` = ? WHERE id = ?' );
			foreach( $id as $siteId=>$site ) {
				$pdoSt->execute( array($site['disabled'], $site['order'], $siteId) );
			}
			// Delete needed cache
			$this->_cache->delete( array('shareable_all', 'shareable_enabled', 'shareable_disabled') );
			return $pdoSt->rowCount();
		}

	}

?>
