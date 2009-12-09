<?php
// $Id: Exceptions.php 2789 2009-11-20 08:27:46Z alexc $

/**
 * Zula Framework Rss Exceptions
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Robert Clipsham
 * @copyright Copyright (C) 2008, Robert Clipsham
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Rss
 */

 	class Rss_NoFeedInfo extends Exception {}
	class Rss_RemoteFeed extends Exception {}
	class Rss_NoSuchNameSpace extends Exception {}
	class Rss_NoSuchElement extends Exception {}
	class Rss_ItemNoExist extends Exception {}

?>
