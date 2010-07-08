<?php

/**
 * Zula Framework Uploader
 * --- Exceptions
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Uploader
 */

	class Uploader_Exception extends Zula_Exception {}

	## Uploader lib
	class Uploader_NotEnabled extends Uploader_Exception {}
	class Uploader_NoExist extends Uploader_Exception {}

	## Uploader_File lib
	class Uploader_MaxFileSize extends Uploader_Exception {}
	class Uploader_PartialUpload extends Uploader_Exception {}
	class Uploader_NoTmpDir extends Uploader_Exception {}
	class Uploader_NoWrite extends Uploader_Exception {}
	class Uploader_FileBlocked extends Uploader_Exception {}

	class Uploader_InvalidMime extends Uploader_Exception {}
	class Uploader_InvalidExtension extends Uploader_Exception {}

?>
