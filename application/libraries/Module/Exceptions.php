<?php

/**
 * Zula Framework Module Exceptions
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Module
 */

	class Module_NoExist extends Zula_Exception {}
	class Module_Disabled extends Module_NoExist {}
	class Module_NotInstallable extends Zula_Exception {}

	class Module_UnableToLoad extends Zula_Exception {}
 	class Module_ControllerNoExist extends Module_UnableToLoad {}
 	class Module_NoPermission extends Module_UnableToLoad {}
	class Module_AjaxOnly extends Module_UnableToLoad {}

?>
