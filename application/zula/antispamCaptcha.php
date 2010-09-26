<?php

/**
 * Zula Framework
 * --- Creates the antispam captcha image to be displayed to the user via the
 * hard-coded 'antispam/captcha/{id}' route'
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula
 */

	$captchaId = substr( $rawRequestPath, 17 );
	if ( isset( $_SESSION['antispam']['captcha'][ $captchaId ] ) ) {
		$antispam = new Antispam( 'captcha' );
		$antispam->outputPng( $_SESSION['antispam']['captcha'][ $captchaId ] );
		return true;
	}
	// Will only ever occur if something goes wrong up there.
	header( 'HTTP/1.1 404 Not Found', true, 404 );
	return false;

?>