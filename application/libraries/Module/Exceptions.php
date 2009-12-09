<?php
// $Id: Exceptions.php 2789 2009-11-20 08:27:46Z alexc $

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

 	class Module_NoExist extends Exception {}
 	class Module_Disabled extends Module_NoExist {}
 	class Module_NotInstallable extends Exception {}

 	class Module_UnableToLoad extends Exception {}
 	class Module_ControllerNoExist extends Module_UnableToLoad {}
 	class Module_NoPermission extends Module_UnableToLoad {}
 	class Module_AjaxOnly extends Module_UnableToLoad {}

?>
