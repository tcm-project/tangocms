<?php

/**
 * Zula Framework Model (Groups)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Groups
 */

	class Groups_model extends Zula_ModelBase {

		/**
		 * Get all groups that do not inherit anything
		 *
		 * @return array
		 */
		public function getGroups() {
			$query = $this->_sql->query(
										'SELECT groups.*, COUNT(users.id) AS user_count
										 FROM
										 	{PREFIX}groups AS groups
										 	JOIN {PREFIX}acl_roles AS roles ON groups.role_id = roles.id AND roles.parent_id = 0
										 	LEFT JOIN {PREFIX}users AS users ON users.group = groups.id
										 GROUP BY groups.id'
										);
			return $query->fetchAll( PDO::FETCH_ASSOC );
		}

	}

?>
