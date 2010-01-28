<?php

/**
 * Zula Framework UGmanager Exceptions
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_UGManager
 */

	class UGManager_UserExists extends Zula_Exception {}

	class UGManager_GroupNoExist extends Zula_Exception {}
	class UGManager_UserNoExist extends Zula_Exception {}

	class UGManager_InvalidActivationCode extends Zula_Exception {}
	class UGManager_InvalidResetCode extends Zula_Exception {}

	class UGManager_GroupExists extends Zula_Exception {}
	class UGManager_InvalidInheritance extends Zula_Exception {}
	class UGManager_InvalidName extends Zula_Exception {}

	class UGManager_InvalidGroup extends Zula_Exception {}
	class UGManager_InvalidUser extends Zula_Exception {}

?>
