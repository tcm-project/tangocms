<?php

/**
 * Zula Framework Module
 * Exceptions
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Session
 */

 	class Session_InvalidCredentials extends Exception {}
 	class Session_UserNotActivated extends Session_InvalidCredentials {}

	class Session_InvalidResetCode extends Exception {}

?>
