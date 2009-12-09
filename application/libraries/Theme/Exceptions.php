<?php
// $Id: Exceptions.php 2789 2009-11-20 08:27:46Z alexc $

/**
 * Zula Framework Theme Exceptions
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Theme
 */

	class Theme_NoExist extends Exception {}
	class Theme_DetailNoExist extends Exception {}
	class Theme_SectorNoExist extends Exception {}

	class Theme_UnableToDelete extends Exception {}
	class Theme_CssNoExist extends Exception {}

	class Theme_Layout_NoSectorMap extends Exception {}
	class Theme_Layout_ControllerNoExist extends Exception {}

?>
