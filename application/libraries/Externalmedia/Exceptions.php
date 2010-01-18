<?php

/**
 * Zula Framework Exceptions
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_ExternalMedia
 */

	class Externalmedia_Exception extends Zula_Exception {}
 	class ExternalMedia_NoDriver extends Externalmedia_Exception {}
	class ExternalMedia_DriverError extends Externalmedia_Exception {}

	class ExternalMediaDriver_NoRead extends Externalmedia_Exception {}
	class ExternalMediaDriver_InvalidID extends Externalmedia_Exception {}

?>
