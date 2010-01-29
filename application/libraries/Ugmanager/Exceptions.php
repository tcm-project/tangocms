<?php

/**
 * Zula Framework Ugmanager Exceptions
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_ugmanager
 */

	class Ugmanager_UserExists extends Zula_Exception {}

	class Ugmanager_GroupNoExist extends Zula_Exception {}
	class Ugmanager_UserNoExist extends Zula_Exception {}

	class Ugmanager_InvalidActivationCode extends Zula_Exception {}
	class Ugmanager_InvalidResetCode extends Zula_Exception {}

	class Ugmanager_GroupExists extends Zula_Exception {}
	class Ugmanager_InvalidInheritance extends Zula_Exception {}
	class Ugmanager_InvalidName extends Zula_Exception {}

	class Ugmanager_InvalidGroup extends Zula_Exception {}
	class Ugmanager_InvalidUser extends Zula_Exception {}

?>
