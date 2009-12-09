<?php
// $Id: Exceptions.php 2789 2009-11-20 08:27:46Z alexc $

/**
 * Zula Framework UGmanager Exceptions
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_UGManager
 */

	class UGManager_UserExists extends Exception {}

	class UGManager_GroupNoExist extends Exception {}
	class UGManager_UserNoExist extends Exception {}

	class UGManager_InvalidActivationCode extends Exception {}
	class UGManager_InvalidResetCode extends Exception {}

	class UGManager_GroupExists extends Exception {}
	class UGManager_InvalidInheritance extends Exception {}
	class UGManager_InvalidName extends Exception {}

	class UGManager_InvalidGroup extends Exception {}
	class UGManager_InvalidUser extends Exception {}

?>
