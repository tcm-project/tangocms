<?php

/**
 * Zula Framework View
 * A simple tag replacement engine that also allows for PHP code
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_View
 */

	class View_FileNoExist extends Zula_Exception {}

	class View_TagNotAssigned extends Zula_Exception {}
	class View_InvalidTagValue extends Zula_Exception {}

	class View_FormValueNoExist extends Zula_Exception {}
	class View_HelperNoExist extends Zula_Exception {}

?>
