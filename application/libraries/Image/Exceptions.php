<?php
// $Id: Exceptions.php 2815 2009-12-02 10:36:33Z alexc $

/**
 * Zula Framework Image Exceptions
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Image
 */

	class Image_Exception extends Exception {}
	
	class Image_LoadFailed extends Image_Exception {}
 	class Image_NoGd extends Image_LoadFailed {}
 	class Image_FileNoExist extends Image_LoadFailed {}	
 	class Image_SaveFailed extends Image_Exception {}	

?>
